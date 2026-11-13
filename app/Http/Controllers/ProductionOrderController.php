<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderAllRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderExportPDFRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderFindRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderStoreRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderUpdateRequest;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderCollection;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderResource;
use App\Models\File;
use App\Models\Piece;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\Supply;
use App\Services\FileService;
use App\Services\ProductionOrderCurveService;
use App\Services\QrCodeService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class ProductionOrderController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(ProductionOrderAllRequest $request, $technical_sheet_id = null)
    {
        try {
            $production_orders = ProductionOrder::with(['strokes', 'supplier', 'fabric', 'pieces', 'technical_sheet' => ['product', 'products', 'garment_type'], 'production_order_details' => ['production_order_detail_quantities', 'model']])
                ->when($technical_sheet_id, function ($query) use ($technical_sheet_id) {
                    return $query->where('technical_sheet_id', $technical_sheet_id);
                })
                ->when($request->has('has_programations'), function ($query) use ($request) {
                    if ($request->boolean('has_programations')) {
                        return $query->whereHas('production_order_details', function ($r) {
                            $r->whereNotNull('settings->date');
                        });
                    }

                    return $query->whereDoesntHave('production_order_details', function ($r) {
                        $r->whereNotNull('settings->date');
                    });
                })
                ->when($request->filled('processes_id') || $request->filled('start_date') || $request->filled('end_date'),
                    function ($query) use ($request) {
                        return $query->whereHas('production_order_details', function ($r) use ($request) {
                            if ($request->filled('processes_id')) {
                                $r->whereIn('model_id', $request->input('processes_id'));
                            }

                            if ($request->filled('start_date')) {
                                $r->whereNotNull('settings->date')
                                    ->where('settings->date', '>=', $request->date('start_date')->format('Y-m-d'));
                            }

                            if ($request->filled('end_date')) {
                                $r->whereNotNull('settings->date')
                                    ->where('settings->date', '<=', $request->date('end_date')->format('Y-m-d'));
                            }
                        });
                    }
                )
                ->when($request->boolean('with_typologies'), function ($query) {
                    return $query->with(['technical_sheet' => ['technical_sheet_details_processes' => ['typology']]]);
                })
                ->when($request->filled('process_id'), function ($query) use ($request) {
                    $workstation = $request->filled('model_type') ? $request->input('model_type')::find($request->integer('model_id')) : null;

                    return $request->boolean('chain')
                        ? $query->chainPending($request->integer('process_id'))
                        : $query->programmableIn($request->integer('process_id'), $workstation, $request->boolean('pending_previous'));
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $production_orders = $production_orders->paginate($request->integer('per_page', $production_orders->count()));

            return $this->successResponse(
                new ProductionOrderCollection($production_orders),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function find(ProductionOrderFindRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::with(['technical_sheet' => ['product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']], 'products', 'garment_type', 'subgroup', 'collection', 'photo_d', 'photo_t'], 'production_order_details' => ['production_order_detail_quantities', 'model'], 'fabric' => ['model'], 'pieces', 'rolls' => ['model' => ['supply_type', 'production_order_details']], 'reassignments' => ['model' => ['technical_sheet' => ['product']]], 'derivatives' => ['model' => ['technical_sheet' => ['product']]]])
                ->findOrFail($id);

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function store(ProductionOrderStoreRequest $request)
    {
        try {
            $result = DB::select('CALL generate_production_order_consecutive()');
            $production_order = new ProductionOrder;
            $consecutive = $result[0]->consecutive;

            $production_order->consecutive = $consecutive;
            $production_order->uuid = Str::uuid();
            $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
            $production_order->efficiency = $request->input('efficiency');
            $production_order->cut = $request->input('cut');
            $production_order->width = $request->input('width');
            $production_order->pocket_fabric = $request->boolean('pocket_fabric');
            $production_order->observation = $request->input('observation');
            $production_order->status = $request->input('status');
            $production_order->settings = $request->input('settings');
            $production_order->production_place = $request->input('production_place');

            if ($request->input('supplier_id')) {
                $production_order->supplier_id = $request->integer('supplier_id');
            }

            $production_order->save();

            if ($request->filled('strokes_file.strokes_file_id')) {

                $file = File::findOrFail($request->input('strokes_file.strokes_file_id'));

                File::create([
                    'model_id' => $production_order->id,
                    'model_type' => ProductionOrder::class,
                    'file_type_id' => $file->file_type_id,
                    'file_subtype_id' => $file->file_subtype_id,
                    'name' => $file->name,
                    'path' => $file->path,
                    'mime' => $file->mime,
                    'extension' => $file->extension,
                    'size' => $file->size,
                    'metadata' => $file->metadata,
                ]);
            }

            if ($request->hasFile('strokes_file')) {
                app(FileService::class)->save(
                    $production_order,
                    $request->file('strokes_file.file'),
                    $request->integer('strokes_file.photo_type_id'),
                    $request->integer('strokes_file.photo_subtype_id'),
                    'public',
                    'production_order'
                );
            }

            $production_order->color()->sync([$request->input('color_id')]);

            if ($request->boolean('reasigned_curve')) {
                app(ProductionOrderCurveService::class)->reassign($request->input('reassigned_origins', []), $production_order->id);
            }

            $this->save_details($request->input('production_order_details', []), $production_order->id);

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function update(ProductionOrderUpdateRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::findOrFail($id);

            $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
            $production_order->efficiency = $request->input('efficiency');
            $production_order->cut = $request->input('cut');
            $production_order->width = $request->input('width');
            $production_order->pocket_fabric = $request->boolean('pocket_fabric');
            $production_order->observation = $request->input('observation');
            $production_order->status = $request->input('status');
            $production_order->settings = $request->input('settings');
            $production_order->production_place = $request->input('production_place');

            if ($request->input('supplier_id')) {
                $production_order->supplier_id = $request->integer('supplier_id');
            }

            $production_order->save();

            if ($request->hasFile('strokes_file')) {
                app(FileService::class)->save_document(
                    $production_order,
                    $request->file('strokes_file.file'),
                    $request->integer('strokes_file.photo_type_id'),
                    $request->integer('strokes_file.photo_subtype_id'),
                    'public',
                    'production_order'
                );
            }

            $production_order->color()->sync([$request->input('color_id')]);

            // Lo que la edición quita (destino en 0, pieza o rollo quitado); el Request ya impidió hacerlo en un lote empezado
            $removed = app(ProductionOrderCurveService::class)->removedDetails($production_order->id, $request->input('production_order_details', []));

            if ($request->boolean('reasigned_curve')) {
                app(ProductionOrderCurveService::class)->reassign($request->input('reassigned_origins', []), $production_order->id);
            }

            $this->save_details($request->input('production_order_details', []), $production_order->id);

            ProductionOrderDetailQuantities::whereIn('production_order_detail_id', $removed['zero_products'])->update(['quantity' => 0]);
            ProductionOrderDetail::whereIn('id', $removed['delete'])->get()->each->delete();

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function save_details(array $production_order_details, int $production_order_id)
    {
        try {
            foreach ($production_order_details as $production_order_detail_item) {

                $model_type = $production_order_detail_item['model_type'];

                if ($model_type === Product::class) {
                    $sizes = $production_order_detail_item['sizes'] ?? [];
                    if (array_sum(array_column($sizes, 'quantity')) === 0) {
                        continue;
                    }
                }

                if ($model_type === Supply::class) {

                    if (! empty($production_order_detail_item['rows'])) {

                        $quantities = [];

                        foreach ($production_order_detail_item['rows'] as $row) {
                            $quantities = array_merge($quantities, $row['sizes'] ?? []);
                        }

                        if (array_sum(array_column($quantities, 'quantity')) === 0) {
                            continue;
                        }

                    } else {
                        $quantity = data_get($production_order_detail_item, 'settings.quantity');

                        if ($quantity === null || $quantity <= 0) {
                            continue;
                        }
                    }
                }

                if ($model_type === Piece::class) {

                    $quantity = data_get($production_order_detail_item, 'settings.quantity');

                    if ($quantity === null || $quantity <= 0) {
                        continue;
                    }
                }

                $production_order_detail = ProductionOrderDetail::firstOrCreate(
                    [
                        'production_order_id' => $production_order_id,
                        'model_type' => $model_type,
                        'model_id' => $production_order_detail_item['model_id'],
                        'destination' => $production_order_detail_item['destination'] ?? null,
                    ]
                );

                if ($model_type === Supply::class) {

                    if (! empty($production_order_detail_item['rows'])) {

                        foreach ($production_order_detail_item['rows'] as $row) {
                            foreach ($row['sizes'] as $size) {
                                ProductionOrderDetailQuantities::updateOrCreate([
                                    'id' => $size['id'],
                                ], [
                                    'production_order_detail_id' => $production_order_detail->id,
                                    'size_id' => $size['size_id'],
                                    'quantity' => $size['quantity'],
                                ]);
                            }
                        }

                    } else {
                        $production_order_detail->settings = $production_order_detail_item['settings'] ?? [];
                        $production_order_detail->save();
                    }
                }

                if ($model_type === Product::class) {

                    foreach ($production_order_detail_item['sizes'] as $size) {
                        ProductionOrderDetailQuantities::updateOrCreate(
                            [
                                'production_order_detail_id' => $production_order_detail->id,
                                'size_id' => $size['size_id'],
                            ],
                            [
                                'quantity' => $size['quantity'],
                            ]
                        );
                    }

                }

                if ($model_type === Piece::class) {
                    $production_order_detail->settings = $production_order_detail_item['settings'] ?? [];
                    $production_order_detail->save();
                }
            }

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function pdf(ProductionOrderExportPDFRequest $request, string $uuid)
    {
        try {
            $production_order = ProductionOrder::with([
                'technical_sheet' => [
                    'collection',
                    'subgroup',
                    'color',
                    'waistband_type',
                    'garment_type',
                    'wash_tone',
                    'boot_type',
                    'yoke_type',
                    'back_type',
                    'technical_sheet_details' => ['typology'],
                    'pattern_maker' => ['person', 'position'],
                    'supplies' => ['supply_type'],
                    'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                    'products' => ['trademark'],
                ],
                'production_order_details' => ['production_order_detail_quantities', 'model'],
                'fabric' => ['model'],
                'pieces',
                'rolls' => ['model' => ['supply_type', 'production_order_details']],
                'derivatives' => ['production_order' => ['technical_sheet' => ['product']]],
                'reassignments' => ['model' => ['technical_sheet' => ['product']]],
            ])->where('uuid', $uuid)->firstOrFail();

            $pdfUrl = URL::signedRoute(
                'production_orders.technical_sheets.pdf',
                [
                    'uuid' => $production_order->uuid,
                ]
            );

            $qr = app(QrCodeService::class)->generate($pdfUrl);

            $pdf = Pdf::loadView(
                'pdf.ProductionOrder.pdf',
                [
                    'production_order' => $production_order,
                    'qr' => $qr,
                ]
            )->setPaper('a4', 'landscape');

            $dompdf = $pdf->getDomPDF();

            $dompdf->render();

            $canvas = $dompdf->getCanvas();

            $canvas->page_text(
                730,
                565,
                'Página {PAGE_NUM} de {PAGE_COUNT}',
                null,
                9,
                [0, 0, 0]
            );

            $tempDir = storage_path('app/temp');

            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $mainPdf = $tempDir.'/orden_'.$production_order->id.'.pdf';

            file_put_contents(
                $mainPdf,
                $dompdf->output()
            );

            $fpdi = new Fpdi;

            $pageCount = $fpdi->setSourceFile($mainPdf);

            for ($page = 1; $page <= $pageCount; $page++) {

                $tpl = $fpdi->importPage($page);

                $size = $fpdi->getTemplateSize($tpl);

                $fpdi->AddPage(
                    $size['orientation'],
                    [$size['width'], $size['height']]
                );

                $fpdi->useTemplate($tpl);
            }

            if ($production_order->strokes) {

                $stroke = $production_order->strokes;

                $strokePath = public_path(
                    str_replace(
                        url('/'),
                        '',
                        parse_url($stroke->path, PHP_URL_PATH)
                    )
                );

                if (file_exists($strokePath)) {

                    $pageCount = $fpdi->setSourceFile($strokePath);

                    for ($page = 1; $page <= $pageCount; $page++) {

                        $tpl = $fpdi->importPage($page);

                        $size = $fpdi->getTemplateSize($tpl);

                        $fpdi->AddPage(
                            $size['orientation'],
                            [$size['width'], $size['height']]
                        );

                        $fpdi->useTemplate($tpl);
                    }
                }
            }

            @unlink($mainPdf);

            $has_stara = $production_order->production_order_details->contains(
                fn ($item) => $item->destination === 'STARA'
            );

            $fileName = 'PO-'.
                ($production_order->technical_sheet->product->code ?? 'SIN-CODIGO').
                '-'.
                ($production_order->cut ?? 'SIN-LOTE');

            if ($has_stara) {
                $staraReference = optional($production_order->technical_sheet->products->first())->code;

                if ($staraReference) {
                    $fileName .= ', '.$staraReference.'-'.($production_order->cut ?? 'SIN-LOTE');
                }
            }

            $fileName .= '.pdf';

            return response(
                $fpdi->Output('S'),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$fileName.'"',
                ]
            );
        } catch (\Throwable $e) {

            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
