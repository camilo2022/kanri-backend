<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderFindRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderAllRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderExportPDFRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderStoreRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderUpdateRequest;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderCollection;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderResource;
use App\Models\File;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\Supply;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\URL;
use App\Services\QrCodeService;
use Illuminate\Support\Str;

class ProductionOrderController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(ProductionOrderAllRequest $request, $technical_sheet_id)
    {
        try {
            $production_orders = ProductionOrder::with(['strokes', 'fabric', 'pieces', 'technical_sheet' => ['product', 'products'], 'production_order_details' => ['production_order_detail_quantities']])
                ->whereHas('technical_sheet', fn($r) => $r->where('id', $technical_sheet_id))
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
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function find(ProductionOrderFindRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::with(['model' => ['production_order_details' => ['production_order_detail_quantities', 'model'], 'technical_sheet' => ['product' => ['trademark' => ['sizes']]]], 'technical_sheet' => ['product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']], 'products', 'garment_type', 'subgroup', 'collection', 'photo_d', 'photo_t',], 'production_order_details' => ['production_order_detail_quantities', 'model'], 'fabric' => ['model'], 'pieces', 'rolls' => ['production_orders']])
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
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function store(ProductionOrderStoreRequest $request)
    {
        try {
            $result = DB::select("CALL generate_production_order_code()");
            $production_order = new ProductionOrder();
            $consecutive = $result[0]->consecutive;

            $production_order->consecutive = $consecutive;
            $production_order->uuid = Str::uuid();
            if ($request->boolean('reasigned_curve')) {
                $production_order->model_id = $request->input('production_order_id');
                $production_order->model_type = ProductionOrder::class;
            }
            $production_order->date = $request->input('date');
            $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
            $production_order->efficiency = $request->input('efficiency');
            $production_order->cut = $request->input('cut');
            $production_order->width = $request->input('width');
            $production_order->pocket_fabric = $request->boolean('pocket_fabric');
            $production_order->observation = $request->input('observation');
            $production_order->status = $request->input('status');
            $production_order->settings = $request->input('settings');

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

            $this->save_details($request->input('production_order_details', []), $production_order->id);

            $production_order->auditAttach('pieces', $request->array('pieces'));
            $production_order->auditAttach('rolls', $request->array('rolls'));

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function update(ProductionOrderUpdateRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::findOrFail($id);

            $production_order->date = $request->input('date');
            $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
            $production_order->efficiency = $request->input('efficiency');
            $production_order->cut = $request->input('cut');
            $production_order->width = $request->input('width');
            $production_order->pocket_fabric = $request->boolean('pocket_fabric');
            $production_order->observation = $request->input('observation');
            $production_order->status = $request->input('status');
            $production_order->settings = $request->input('settings');

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

            $this->save_details($request->input('production_order_details', []), $production_order->id);

            $production_order->pieces()->sync($request->array('pieces'));
            $production_order->rolls()->sync($request->array('rolls'));

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function save_details(array $production_order_details, int $production_order_id)
    {
        try {
            logger($production_order_details);
            foreach ($production_order_details as $production_order_detail_item) {
                $quantities = [];

                if ($production_order_detail_item['model_type'] === Product::class) {
                    $quantities = $production_order_detail_item['sizes'];
                }

                if ($production_order_detail_item['model_type'] === Supply::class) {
                    foreach ($production_order_detail_item['rows'] as $row) {
                        $quantities = array_merge($quantities, $row['sizes']);
                    }
                }

                if (array_sum(array_column($quantities, 'quantity')) === 0) {
                    continue;
                }

                $production_order_detail = new ProductionOrderDetail();

                $production_order_detail = ProductionOrderDetail::firstOrCreate([
                    'production_order_id' => $production_order_id,
                    'model_type' => $production_order_detail_item['model_type'],
                    'model_id' => $production_order_detail_item['model_id'],
                    'destination' => $production_order_detail_item['destination'],
                ]);

                if ($production_order_detail['model_type'] === Supply::class) {
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
                }

                if ($production_order_detail['model_type'] === Product::class) {
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
                    'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                    'products',
                    'photo_d',
                    'photo_t',
                    'garment_type',
                    'collection',
                    'model' => ['product'],
                    'derivatives' => ['product'],
                ],
                'production_order_details' => ['production_order_detail_quantities', 'model'],
                'fabric.model',
                'pieces',
                'rolls',
                'model' => ['technical_sheet' => ['product']],
                'derivatives' => ['technical_sheet' => ['product']],
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
                "Página {PAGE_NUM} de {PAGE_COUNT}",
                null,
                9,
                [0, 0, 0]
            );

            $tempDir = storage_path('app/temp');

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $mainPdf = $tempDir . '/orden_' . $production_order->id . '.pdf';

            file_put_contents(
                $mainPdf,
                $dompdf->output()
            );

            $fpdi = new Fpdi();

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
                fn($item) => $item->destination === 'STARA'
            );

            $fileName = 'PO-' .
                ($production_order->technical_sheet->product->code ?? 'SIN-CODIGO') .
                '-' .
                ($production_order->cut ?? 'SIN-LOTE');

            if ($has_stara) {
                $staraReference = optional($production_order->technical_sheet->products->first())->code;

                if ($staraReference) {
                    $fileName .= ', ' . $staraReference . '-' . ($production_order->cut ?? 'SIN-LOTE');
                }
            }

            $fileName .= '.pdf';

            return response(
                $fpdi->Output('S'),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                ]
            );
        } catch (\Throwable $e) {

            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
