<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\TechnicalSheetExportPDFRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetFindRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetStoreRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetUpdateRequest;
use App\Http\Resources\TechnicalSheet\TechnicalSheetResource;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Services\FileService;
use App\Services\QrCodeService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use App\Traits\ModelTypes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TechnicalSheetController extends Controller
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
                'technical_sheet_details' => ['typology'],
                'technical_sheet_details_processes',
                'pattern_maker' => ['person', 'position'],
                'supplies' => ['supply_type'],
                'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                'products' => ['trademark'],
            ])
                ->when($request->filled('id'), function ($query) use ($request) {
                    $query->where('id', $request->integer('id'));
                })
                ->when($request->filled('product_id'), function ($query) use ($request) {
                    return $query->where('product_id', $request->integer('product_id'));
                })
                ->when($request->filled('uuid'), function ($query) use ($request) {
                    return $query->where('uuid', $request->integer('uuid'));
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
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function store(TechnicalSheetStoreRequest $request)
    {
        try {
            $result = DB::select('CALL generate_technical_sheet_consecutive()');
            $technical_sheet = new TechnicalSheet;

            $technical_sheet->uuid = Str::uuid();
            $technical_sheet->code = $request->input('code');
            $technical_sheet->consecutive = $result[0]->consecutive;
            $technical_sheet->date = $request->input('date');
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
            $technical_sheet->pattern_maker_id = $request->integer('pattern_maker_id');
            $technical_sheet->description = $request->input('description');
            $technical_sheet->observation = $request->input('observation');

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
                201
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
                201
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

    private function save(array $technical_sheet_details, int $technical_sheet_id)
    {
        try {
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

    private function resolveDynamicField($field, $value)
    {

        if (empty($field->model) || empty($value) || ! class_exists($field->model)) {
            return $value;
        }

        $model = $field->model;

        $record = $model::find($value);

        if (! $record) {
            return $value;
        }

        $config = collect($this->getModelTypes())
            ->firstWhere('model', $model);

        if (! $config) {
            return $record->id;
        }

        $option = $config['option'] ?? 'name';

        if (is_array($option)) {
            return collect($option)
                ->map(fn ($path) => data_get($record, $path))
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
                ! empty($settings->dinamic->values) &&
                ! empty($settings->dinamic->body)
            ) {

                $body = collect($settings->dinamic->body);

                $settings->dinamic->values = collect($settings->dinamic->values)
                    ->map(function ($row) use ($body) {
                        foreach (get_object_vars($row) as $field => $value) {
                            $definition = $body->firstWhere('field', $field);

                            if (! $definition) {
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
                ! empty($settings->static->values) &&
                ! empty($settings->static->body)
            ) {

                $body = collect($settings->static->body)
                    ->flatten(1);

                foreach (get_object_vars($settings->static->values) as $field => $value) {

                    $definition = $body->firstWhere('field', $field);

                    if (! $definition) {
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
                'fabric.model',
                'pieces',
                'rolls',
                'derivatives' => ['production_order' => ['technical_sheet' => ['product']]],
                'reassignments' => ['model' => ['technical_sheet' => ['product']]],
            ])->where('uuid', $uuid)->firstOrFail();

            logger()->info('Generating PDF for production order: '.$production_order->uuid, [
                'production_order' => $production_order,
            ]);

            $pdfUrl = URL::signedRoute(
                'technical_sheets.pdf',
                [
                    'uuid' => $production_order->technical_sheet->uuid,
                ]
            );

            $qr = app(QrCodeService::class)->generate($pdfUrl);

            $production_order->technical_sheet->technical_sheet_details =
                $this->formatTechnicalSheetDetails(
                    $production_order->technical_sheet->technical_sheet_details
                );

            $processes = Process::with(['subprocesses' => ['operations']])->where('settings->in_technical_sheet', true)
                ->get();

            $pdf = Pdf::loadView(
                'pdf.TechnicalSheet.pdf',
                [
                    'technical_sheet' => $production_order->technical_sheet,
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
                'Página {PAGE_NUM} de {PAGE_COUNT}',
                null,
                9,
                [0, 0, 0]
            );

            $has_stara = $production_order->production_order_details->contains(
                fn ($item) => $item->destination === 'STARA'
            );

            $fileName = 'TS-'.
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
                $dompdf->output(),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline;  filename="'.$fileName.'"',
                ]
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
}
