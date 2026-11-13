<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\TechnicalSheetExportPDFRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetFindRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetUpdateRequest;
use App\Http\Requests\TechnicalSheet\Transformation\TransformationStoreRequest;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\TechnicalSheet\TechnicalSheetResource;
use App\Models\File;
use App\Models\Process;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Services\FileService;
use App\Services\ProductionOrderCurveService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use App\Services\QrCodeService;
use App\Traits\ModelTypes;

class TransformationController extends Controller
{
    use ApiMessage, ApiResponser, ModelTypes;

    public function find(TechnicalSheetFindRequest $request, $id = null)
    {
        try {
            $technical_sheet = TechnicalSheet::with([
                'collection',
                'subgroup',
                'color',
                'waistband_type',
                'garment_type',
                'wash_tone',
                'boot_type',
                'yoke_type',
                'back_type',
                'technical_sheet_details',
                'pattern_maker' => ['person', 'position'],
                'supplies' => ['supply_type'],
                'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                'products' => ['trademark']
            ])
                ->when($request->filled('id'), function ($query) use ($request) {
                    $query->where('id', $request->integer('id'));
                })
                ->when($request->filled('product_id'), function ($query) use ($request) {
                    return $query->where('product_id', $request->integer('product_id'));
                })
                ->when($request->filled('uuid'), function ($query) use ($request) {
                    return $query->where('uuid', $request->input('uuid'));
                })->firstOrFail();

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
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

    public function store(TransformationStoreRequest $request)
    {
        try {
            $product = new Product();

            $product->code = $request->input('product.code');
            $product->trademark_id = $request->input('product.trademark_id');
            $product->subcategory_id = $request->input('product.subcategory_id');

            $product->save();

            $result = DB::select('CALL generate_technical_sheet_consecutive()');
            $technical_sheet = new TechnicalSheet();

            $technical_sheet->uuid = Str::uuid();
            $technical_sheet->consecutive = $result[0]->consecutive;
            $technical_sheet->status = $request->input('technical_sheet.status');
            $technical_sheet->date = $request->input('technical_sheet.date');
            $technical_sheet->product_id = $product->id;
            $technical_sheet->model_id = $request->input('technical_sheet_id');
            $technical_sheet->model_type = TechnicalSheet::class;
            $technical_sheet->code = $request->input('technical_sheet.code');
            $technical_sheet->collection_id = $request->integer('technical_sheet.collection_id');
            $technical_sheet->garment_type_id = $request->integer('technical_sheet.garment_type_id');
            $technical_sheet->subgroup_id = $request->integer('technical_sheet.subgroup_id');
            $technical_sheet->wash_tone_id = $request->integer('technical_sheet.wash_tone_id');
            $technical_sheet->color_id = $request->integer('technical_sheet.color_id');
            $technical_sheet->waistband_type_id = $request->integer('technical_sheet.waistband_type_id');
            $technical_sheet->measure_of_waistband = $request->integer('technical_sheet.measure_of_waistband');
            $technical_sheet->boot_type_id = $request->integer('technical_sheet.boot_type_id');
            $technical_sheet->yoke_type_id = $request->integer('technical_sheet.yoke_type_id');
            $technical_sheet->back_type_id = $request->integer('technical_sheet.back_type_id');
            $technical_sheet->physical_sample = $request->integer('technical_sheet.physical_sample');
            $technical_sheet->number_of_buttons = $request->integer('technical_sheet.number_of_buttons');
            $technical_sheet->pattern_maker_id = $request->integer('technical_sheet.pattern_maker_id');
            $technical_sheet->description = $request->input('technical_sheet.description');
            $technical_sheet->observation = $request->input('technical_sheet.observation');

            $technical_sheet->save();

            $technical_sheet->auditSync('supplies', $request->input('technical_sheet.supplies', []));

            if ($request->hasFile('technical_sheet.photo_d.file')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('technical_sheet.photo_d.file'),
                    $request->integer('technical_sheet.photo_d.photo_type_id'),
                    $request->integer('technical_sheet.photo_d.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            } elseif ($request->input('technical_sheet.photo_d.id')) {
                $original_file = File::find($request->input('technical_sheet.photo_d.id'));

                if ($original_file) {
                    $new_file = $original_file->replicate();

                    $new_file->model_id = $technical_sheet->id;
                    $new_file->model_type = TechnicalSheet::class;

                    $new_file->name = $request->input('product.code') . '-D';

                    $new_file->save();
                }
            }

            if ($request->hasFile('technical_sheet.photo_t.file')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('technical_sheet.photo_t.file'),
                    $request->integer('technical_sheet.photo_t.photo_type_id'),
                    $request->integer('technical_sheet.photo_t.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            } elseif ($request->input('technical_sheet.photo_t.id')) {
                $original_file = File::find($request->input('technical_sheet.photo_t.id'));

                if ($original_file) {
                    $new_file = $original_file->replicate();

                    $new_file->model_id = $technical_sheet->id;
                    $new_file->model_type = TechnicalSheet::class;

                    $new_file->name = $request->input('product.code') . '-T';

                    $new_file->save();
                }
            }

            $this->save($request->input('technical_sheet.technical_sheet_details', []), $technical_sheet->id);

            $product_stara = null;

            if (!empty($request->input('product_stara'))) {
                $product_stara = new Product();

                $product_stara->code = $request->input('product_stara.code');
                $product_stara->trademark_id = $request->input('product_stara.trademark_id');
                $product_stara->subcategory_id = $request->input('product_stara.subcategory_id');

                $product_stara->save();

                $product_stara->auditAttach('technical_sheets', $technical_sheet->id);
            }

            $result = DB::select("CALL generate_production_order_consecutive()");
            $production_order = new ProductionOrder();
            $consecutive = $result[0]->consecutive;

            $production_order->uuid = Str::uuid();
            $production_order->consecutive = $consecutive;
            $production_order->technical_sheet_id = $technical_sheet->id;
            $production_order->cut = $request->input('production_order.cut');
            $production_order->status = $request->input('production_order.status');

            $production_order->save();

            $this->save_details_production_order($request->input('production_order.production_order_details', []), $production_order->id, $product->id, $product_stara?->id);

            if ($request->boolean('reasigned_curve')) {
                app(ProductionOrderCurveService::class)->reassign($request->input('reassigned_origins', []), $production_order->id);
            }

            return $this->successResponse(
                new ProductResource($product),
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

    public function update(TechnicalSheetUpdateRequest $request, $id)
    {
        try {
            $technical_sheet = TechnicalSheet::findOrFail($id);

            $technical_sheet->date = $request->input('date');
            $technical_sheet->code = $request->input('code');
            $technical_sheet->product_id = $request->integer('product_id');
            $technical_sheet->collection_id = $request->integer('collection_id');
            $technical_sheet->garment_type_id = $request->integer('garment_type_id');
            $technical_sheet->subgroup_id = $request->integer('subgroup_id');
            $technical_sheet->wash_tone_id = $request->integer('wash_tone_id');
            $technical_sheet->color_id = $request->integer('color_id');
            $technical_sheet->waistband_type_id = $request->integer('waistband_type_id');
            $technical_sheet->measure_of_waistband = $request->integer('measure_of_waistband');
            $technical_sheet->boot_type_id = $request->integer('boot_type_id');
            $technical_sheet->yoke_type_id = $request->integer('yoke_type_id');
            $technical_sheet->back_type_id = $request->integer('back_type_id');
            $technical_sheet->physical_sample = $request->integer('physical_sample');
            $technical_sheet->number_of_buttons = $request->integer('number_of_buttons');
            $technical_sheet->description = $request->input('description');
            $technical_sheet->observation = $request->input('observation');
            $technical_sheet->pattern_maker_id = $request->integer('pattern_maker_id');
            $technical_sheet->status = $request->input('status');

            $technical_sheet->save();
            $technical_sheet->auditSync('supplies', $request->input('supplies', []));

            if ($request->hasFile('photo_d')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('photo_d.file'),
                    $request->integer('photo_d.photo_type_id'),
                    $request->integer('photo_d.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            }

            if ($request->hasFile('photo_t')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('photo_t.file'),
                    $request->integer('photo_t.photo_type_id'),
                    $request->integer('photo_t.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            }

            $this->save($request->input('technical_sheet_details', []), $technical_sheet->id);


            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
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

    private function save(array $technical_sheet_details, int $technical_sheet_id)
    {
        // Sin try/catch propio: si falla, el error llega al de store/update y la respuesta no dice «éxito»
        foreach ($technical_sheet_details as $technical_sheet_detail) {
            $detail = TechnicalSheetDetail::updateOrCreate(
                [
                    'technical_sheet_id' => $technical_sheet_id,
                    'model_type' => $technical_sheet_detail['model_type'],
                    'model_id' => $technical_sheet_detail['model_id'],
                ],
                [
                    'status' => $technical_sheet_detail['status'],
                    'settings' => $technical_sheet_detail['settings'] ?? (object) [],
                ]
            );

            if (isset($technical_sheet_detail['typology'])) {
                $detail->auditSync('typology', $technical_sheet_detail['typology']);
            }
        }
    }

    public function save_details_production_order(array $production_order_details, int $production_order_id, int $product_id, ?int $product_stara_id = null)
    {
        foreach ($production_order_details as $item) {

            $quantities = $item['sizes'];

            if (array_sum(array_column($quantities, 'quantity')) === 0) {
                continue;
            }

            $model_id = $item['destination'] === 'STARA'
                ? $product_stara_id
                : $product_id;

            $production_order_detail = ProductionOrderDetail::firstOrCreate(['production_order_id' => $production_order_id, 'model_type' => Product::class, 'model_id' => $model_id, 'destination' => $item['destination']]);

            foreach ($item['sizes'] as $size) {
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

    private function resolveDynamicField($field, $value)
    {

        if (empty($field->model) || empty($value) || !class_exists($field->model)) {
            return $value;
        }

        $model = $field->model;

        $record = $model::find($value);

        if (!$record) {
            return $value;
        }

        $config = collect($this->getModelTypes())
            ->firstWhere('model', $model);

        if (!$config) {
            return $record->id;
        }

        $option = $config['option'] ?? 'name';

        if (is_array($option)) {
            return collect($option)
                ->map(fn($path) => data_get($record, $path))
                ->filter()
                ->implode(' ');
        }

        return data_get($record, $option);
    }

    private function formatTechnicalSheetDetails($details)
    {
        return $details->map(function ($detail) {
            $settings = $detail->settings;

            if (
                isset($settings->dinamic) &&
                !empty($settings->dinamic->values) &&
                !empty($settings->dinamic->body)
            ) {

                $body = collect($settings->dinamic->body);

                $settings->dinamic->values = collect($settings->dinamic->values)
                    ->map(function ($row) use ($body) {
                        foreach (get_object_vars($row) as $field => $value) {
                            $definition = $body->firstWhere('field', $field);

                            if (!$definition) {
                                continue;
                            }

                            if (($definition->type ?? null) === 'boolean') {
                                $row->{$field} = $value ? 'Sí' : 'No';
                                continue;
                            }

                            $row->{$field} = $this->resolveDynamicField(
                                $definition,
                                $value
                            );
                        }

                        return $row;
                    })
                    ->values()
                    ->toArray();
            }

            if (
                isset($settings->static) &&
                !empty($settings->static->values) &&
                !empty($settings->static->body)
            ) {

                $body = collect($settings->static->body)
                    ->flatten(1);

                foreach (get_object_vars($settings->static->values) as $field => $value) {

                    $definition = $body->firstWhere('field', $field);

                    if (!$definition) {
                        continue;
                    }

                    if (($definition->type ?? null) === 'boolean') {
                        $settings->static->values->{$field} = $value ? 'Sí' : 'No';
                        continue;
                    }

                    $settings->static->values->{$field} = $this->resolveDynamicField(
                        $definition,
                        $value
                    );
                }
            }

            $detail->settings = $settings;

            return $detail;
        });
    }

    public function pdf(TechnicalSheetExportPDFRequest $request, string $uuid)
    {
        try {
            $technical_sheet = TechnicalSheet::with([
                'collection',
                'subgroup',
                'color',
                'waistband_type',
                'garment_type',
                'wash_tone',
                'boot_type',
                'yoke_type',
                'back_type',
                'technical_sheet_details',
                'pattern_maker' => ['person', 'position'],
                'supplies' => ['supply_type'],
                'product' => ['trademark' => ['group'], 'subcategory' => ['category']],
                'products' => ['trademark']
            ])->where('uuid', $uuid)->firstOrFail();

            $production_order = ProductionOrder::with([
                'technical_sheet' => ['product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']], 'products', 'photo_d', 'photo_t', 'garment_type', 'collection'],
                'production_order_details' => ['production_order_detail_quantities', 'model'],
                'fabric' => ['model'],
                'pieces',
                'rolls'
            ])->where('id', $request->production_order_id)
                ->where('technical_sheet_id', $technical_sheet->id)
                ->firstOrFail();

            $pdfUrl = URL::signedRoute(
                'technical_sheets.pdf',
                [
                    'uuid' => $technical_sheet->uuid,
                    'production_order_id' => $production_order->id,
                ]
            );

            $qr = app(QrCodeService::class)->generate($pdfUrl);

            $technical_sheet->technical_sheet_details =
                $this->formatTechnicalSheetDetails(
                    $technical_sheet->technical_sheet_details
                );

            $processes = Process::with(['subprocesses' => ['operations']])->where('settings->in_technical_sheet', true)
                ->get();

            $pdf = Pdf::loadView(
                'pdf.TechnicalSheet.technical_sheet',
                [
                    'technical_sheet' => $technical_sheet,
                    'production_order' => $production_order,
                    'processes' => $processes,
                    'qr' => $qr,
                ]
            )->setPaper('a4', 'portrait');

            $dompdf = $pdf->getDomPDF();

            $dompdf->render();

            $canvas = $dompdf->getCanvas();

            $canvas->page_text(
                270,
                815,
                "Página {PAGE_NUM} de {PAGE_COUNT}",
                null,
                9,
                [0, 0, 0]
            );

            $has_stara = $production_order->production_order_details->contains(
                fn($item) => $item->destination === 'STARA'
            );

            $fileName = 'TS-' .
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
                $dompdf->output(),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline;  filename="' . $fileName . '"'
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
