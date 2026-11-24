<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\TechnicalSheetExportPDFRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetFindRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetProcessPdfRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetStoreRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetUpdateRequest;
use App\Http\Resources\TechnicalSheet\TechnicalSheetResource;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Subprocess;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Services\ChromePdfService;
use App\Services\PdfLinkService;
use App\Services\FileService;
use App\Services\ProductivityDetailService;
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

            $this->save($request->input('technical_sheet_details', []), $technical_sheet->id, $request->integer('wash_tone_id') ?: null);

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

            $this->save($request->input('technical_sheet_details', []), $technical_sheet->id, $request->integer('wash_tone_id') ?: null);

            // Lo quitado de la ficha (ya validado): el subproceso con sus operaciones
            foreach ($request->input('removed_details', []) as $item) {
                $model = $item['model_type']::find($item['model_id']);
                $removed = collect([$model])->concat($model instanceof Subprocess ? $model->operations : []);

                TechnicalSheetDetail::where('technical_sheet_id', $technical_sheet->id)
                    ->where(fn ($query) => $removed->each(fn ($item) => $query->orWhere(fn ($own) => $own
                        ->where('model_type', $item::class)
                        ->where('model_id', $item->id))))
                    ->get()
                    ->each->delete();
            }

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

    private function save(array $technical_sheet_details, int $technical_sheet_id, ?int $wash_tone_id = null)
    {
        $keys = collect($technical_sheet_details)
            ->map(fn ($detail) => ($detail['model_type'] ?? '').':'.($detail['model_id'] ?? ''))
            ->unique()
            ->values();

        // Sin try/catch propio: si falla, el error llega al de store/update y la respuesta no dice «éxito»
        foreach ($technical_sheet_details as $technical_sheet_detail) {
            // Un subproceso automático que no aplica a la ficha (LAVADO 3 sin EFECTO TRAPO) no se guarda
            if ($this->inapplicableAutomatic($technical_sheet_detail['model_type'], $technical_sheet_detail['model_id'], $keys, $wash_tone_id)) {
                continue;
            }

            $stored = TechnicalSheetDetail::where('technical_sheet_id', $technical_sheet_id)
                ->where('model_type', $technical_sheet_detail['model_type'])
                ->where('model_id', $technical_sheet_detail['model_id'])
                ->first();

            // Lo Aprobado queda cerrado: si sigue Aprobado se conservan sus tablas y su tipificación aunque lleguen otras
            if ($stored?->status === TechnicalSheetDetail::APPROVED && $technical_sheet_detail['status'] === TechnicalSheetDetail::APPROVED) {
                continue;
            }

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

        // Los que ya estaban guardados y ahora no aplican
        TechnicalSheetDetail::where('technical_sheet_id', $technical_sheet_id)
            ->where('model_type', Subprocess::class)
            ->get()
            ->filter(fn (TechnicalSheetDetail $detail) => $this->inapplicableAutomatic($detail->model_type, $detail->model_id, $keys, $wash_tone_id))
            ->each->delete();
    }

    private function inapplicableAutomatic(string $model_type, $model_id, $keys, ?int $wash_tone_id): bool
    {
        if ($model_type !== Subprocess::class) {
            return false;
        }

        $subprocess = Subprocess::find($model_id);

        return $subprocess
            && ! ($subprocess->settings->in_technical_sheet ?? false)
            && ! app(ProductivityDetailService::class)->sheetSubprocessApplies($subprocess, $keys, $wash_tone_id);
    }

    private function resolveDynamicField($field, $value)
    {

        if (empty($field->model) || empty($value) || ! class_exists($field->model)) {
            return $value;
        }

        $model = $field->model;

        // También los desactivados: la ficha guarda el id y el registro pudo desactivarse después (p. ej. PESPUNTE)
        $record = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model), true)
            ? $model::withTrashed()->find($value)
            : $model::find($value);

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

                            if (is_object($value)) {
                                $row->{$field} = $value->name ?? '-';

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

    /**
     * Nombre de la ficha técnica en PDF: TS- + la referencia y el lote de la orden (TS-BL563082-A).
     */
    private function pdfName(TechnicalSheet $technical_sheet, ProductionOrder $production_order): string
    {
        return 'TS-'.($technical_sheet->product->code ?? '-').'-'.($production_order->cut ?? '-');
    }

    public function pdf(TechnicalSheetExportPDFRequest $request, string $uuid)
    {
        try {
            // La dirección firmada que pidió el sistema entrega el PDF que ya se generó (ver PdfLinkService)
            if ($request->routeIs('technical_sheets.pdf') && ($cached = app(PdfLinkService::class)->cached('technical_sheets.pdf', $uuid, $request->route('name')))) {
                return $cached;
            }

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
                    'pattern_maker' => ['person'],
                    'supplies' => ['supply_type'],
                    'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                    'products' => ['trademark'],
                ],
                'production_order_details' => ['production_order_detail_quantities', 'model'],
                'fabric' => ['model'],
                'pieces',
                'rolls',
                'derivatives' => ['production_order' => ['technical_sheet' => ['product']]],
                'reassignments' => ['model' => ['technical_sheet' => ['product']]],
            ])->where('uuid', $uuid)->firstOrFail();
            
            $pdfUrl = URL::signedRoute(
                'technical_sheets.pdf',
                [
                    // La ficha técnica se descarga desde una orden de producción: el QR apunta a esa orden
                    'uuid' => $production_order->uuid,
                ]
            );

            $qr = app(QrCodeService::class)->generate($pdfUrl);

            $name = $this->pdfName($production_order->technical_sheet, $production_order);

            $production_order->technical_sheet->technical_sheet_details =
                $this->formatTechnicalSheetDetails(
                    $production_order->technical_sheet->technical_sheet_details
                );

            $processes = Process::with(['subprocesses_with_trashed' => ['operations_with_trashed']])->where('settings->in_technical_sheet', true)
                ->get();

            // Se imprime con Chrome, con el mismo diseño de la ficha de procesos
            $image = fn (string $path) => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($path)));
            $output = app(ChromePdfService::class)->render(
                view('pdf.TechnicalSheet.pdf', [
                    'technical_sheet' => $production_order->technical_sheet,
                    'production_order' => $production_order,
                    'processes' => $processes,
                    'logo' => $image('images/logo.png'),
                    'qr' => $qr,
                    'name' => $name,
                ])->render(),
                [
                    'footer' => view('pdf.TechnicalSheet.pdf', ['footer' => true, 'kanri' => $image('images/logo-kanri-dark.png')])->render(),
                    'margin_top' => 12,
                    'margin_bottom' => 22,
                    'margin_side' => 20,
                ]
            );

            $fileName = $name.'.pdf';

            // Desde el sistema (ruta con sesión) se guarda y se devuelve la dirección firmada que termina en el nombre
            if (! $request->routeIs('technical_sheets.pdf')) {
                return $this->successResponse(['url' => app(PdfLinkService::class)->store('technical_sheets.pdf', $uuid, $fileName, $output)]);
            }

            return response(
                $output,
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

    /**
     * Ficha de procesos en PDF: la ficha técnica sin orden de producción (sin lote, curva ni destinos), con sus procesos,
     * subprocesos y operaciones y, al final, la línea de trabajo por la que pasa la referencia.
     */
    public function process_pdf(TechnicalSheetProcessPdfRequest $request, string $uuid)
    {
        try {
            // La dirección firmada que pidió el sistema entrega el PDF que ya se generó (ver PdfLinkService)
            if ($request->routeIs('technical_sheets.process_pdf') && ($cached = app(PdfLinkService::class)->cached('technical_sheets.process_pdf', $uuid, $request->route('name')))) {
                return $cached;
            }

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
                'pattern_maker' => ['person', 'position' => ['area']],
                'product' => ['trademark' => ['group'], 'subcategory' => ['category']],
                'products' => ['trademark'],
            ])->withCount('production_orders')->where('uuid', $uuid)->firstOrFail();

            $line =app(ProductivityDetailService::class)->sheetLine($technical_sheet);

            $technical_sheet->technical_sheet_details = $this->formatTechnicalSheetDetails($technical_sheet->technical_sheet_details);

            // La ficha de procesos (workflow sheet) se llama WS- + la referencia
            $name = 'WS-'.($technical_sheet->product->code ?? '-');

            // Se imprime con Chrome para que salga como el diseño (esquinas redondeadas, sombras, diagrama en SVG)
            $image = fn (string $path) => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($path)));
            $output = app(ChromePdfService::class)->render(
                view('pdf.ProcessSheet.pdf', [
                    'technical_sheet' => $technical_sheet,
                    'processes' => Process::with(['subprocesses_with_trashed' => ['operations_with_trashed']])->where('settings->in_technical_sheet', true)->get(),
                    'line' => $line,
                    'name' => $name,
                    'logo' => $image('images/logo.png'),
                    'qr' => app(QrCodeService::class)->generate(URL::signedRoute('technical_sheets.process_pdf', ['uuid' => $technical_sheet->uuid])),
                ])->render(),
                [
                    'footer' => view('pdf.ProcessSheet.pdf', ['footer' => true, 'kanri' => $image('images/logo-kanri-dark.png')])->render(),
                    'margin_top' => 12,
                    'margin_bottom' => 22,
                    'margin_side' => 20,
                ]
            );

            // Desde el sistema (ruta con sesión) se guarda y se devuelve la dirección firmada que termina en el nombre
            if (! $request->routeIs('technical_sheets.process_pdf')) {
                return $this->successResponse(['url' => app(PdfLinkService::class)->store('technical_sheets.process_pdf', $uuid, $name.'.pdf', $output)]);
            }

            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$name.'.pdf"',
            ]);
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
