@php
    use App\Models\Supply;
    use App\Models\Product;
    use App\Models\Process;
@endphp

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <style>
        {!! file_get_contents(resource_path('css/TechnicalSheet/pdf.styles.css')) !!}
    </style>

</head>

<body>
    <table class="header-container">
        <tr>
            <td class="logo-cell" rowspan="3" style="width: 30%;">
                @if (file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}">
                @else
                    <div class="logo-placeholder">BLESS</div>
                @endif
            </td>

            <td class="title-primary" style="width: 40%;">GESTIÓN DE DISEÑO</td>
            <td class="meta-cell" style="width: 10%;">GP-FR-01 Versión 01</td>
            <td class="qr-cell" rowspan="2"><img src="{{ $qr }}" width="90"></td>
        </tr>

        <tr>
            <td class="title-primary" rowspan="2" style="width: 40%;">FICHA TÉCNICA</td>
            <td class="meta-cell" style="width: 10%;">Vigente desde 13/04/2026</td>
        </tr>

        <tr>
            <td class="meta-cell" colspan="2">Impreso: {{ now()->format('d/m/Y h:i A') }}</td>
        </tr>

    </table>

    <div class="section">
        <table class="info-table">
            @if ($production_order->model || $production_order->derivatives)
                <tr>
                    <td class="label-info-table" style="text-align:center;">-</td>
                    <td class="label-info-table" style="text-align:center;">Origen</td>
                    <td class="label-info-table" style="text-align:center;">Derivados</td>
                </tr>

                <tr>
                    <td class="label-info-table" style="text-align:center;">Transformación</td>
                    <td class="label-info-table origin-value">
                        {{ $production_order->technical_sheet->model->product->code ?? '-' }}</td>
                    <td class="label-info-table origin-value">
                        @forelse ($production_order->technical_sheet->derivatives as $derivative)
                            {{ $derivative->product?->code ?? '-' }}

                            @if (!$loop->last)
                                <br>
                            @endif
                        @empty
                            -
                        @endforelse
                    </td>
                </tr>

                <tr>
                    <td class="label-info-table" style="text-align:center;">Reasignación</td>
                    <td class="label-info-table derivation-value">
                        @if ($production_order->model)
                            {{ ($production_order->model->technical_sheet?->product?->code ?? '-') . '-' . ($production_order->model->cut ?? '-') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="label-info-table derivation-value">
                        @forelse ($production_order->derivatives as $derivative)
                            {{ ($derivative->technical_sheet?->product?->code ?? '-') . '-' . ($derivative->cut ?? '-') }}

                            @if (!$loop->last)
                                <br>
                            @endif
                        @empty
                            -
                        @endforelse
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">INFORMACIÓN PRODUCTO</div>

        @php
            $has_stara = $production_order->production_order_details->contains(
                fn($item) => $item->destination === 'STARA',
            );
        @endphp

        <table class="info-table">
            @if ($has_stara)
                <tr>
                    <td class="label" rowspan="2">Referencia</td>
                    <td class="value">
                        {{ ($technical_sheet->product->code ?? '-') . '-' . ($production_order->cut ?? '-') }}
                    </td>

                    <td class="label" rowspan="2">Marca</td>
                    <td class="value">{{ $technical_sheet->product->trademark->name ?? '-' }}</td>

                    <td class="label" rowspan="2">Colección</td>
                    <td class="value" rowspan="2">
                        {{ ($technical_sheet->collection->settings->code ?? '-') . ' - ' . ($technical_sheet->collection->name ?? '-') }}
                    </td>
                </tr>

                <tr>
                    <td class="value">
                        {{ optional($technical_sheet->products->first())->code ?? '-' }}-{{ $production_order->cut ?? '-' }}
                    </td>

                    <td class="value">
                        {{ optional($technical_sheet->products->first())->trademark->name ?? '-' }}</td>
                </tr>
            @else
                <tr>
                    <td class="label">Referencia</td>
                    <td class="value">
                        {{ ($technical_sheet->product->code ?? '-') . ' - ' . ($production_order->cut ?? '-') }}
                    </td>

                    <td class="label">Marca</td>
                    <td class="value">{{ $technical_sheet->product->trademark->name ?? '-' }}</td>

                    <td class="label">Colección</td>
                    <td class="value">
                        {{ ($technical_sheet->collection->settings->code ?? '-') . ' - ' . ($technical_sheet->collection->name ?? '-') }}
                    </td>
                </tr>
            @endif

            <tr>
                <td class="label">Grupo</td>
                <td class="value">
                    {{ optional($technical_sheet->product->trademark->group->first())->name ?? '-' }}
                </td>

                <td class="label">Categoría</td>
                <td class="value">
                    {{ optional($technical_sheet->product->subcategory->category->first())->name ?? '-' }}
                </td>

                <td class="label">Subcategoría</td>
                <td class="value">{{ $technical_sheet->product->subcategory->name ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Subgrupo</td>
                <td class="value">{{ $technical_sheet->subgroup->name ?? '-' }}</td>

                <td class="label">Color</td>
                <td class="value">
                    {{ $technical_sheet->color
                        ? ($technical_sheet->color->settings->code ?? '-') . ' - ' . ($technical_sheet->color->name ?? '-')
                        : '#N/A' }}
                </td>

                <td class="label">Tono Lavado</td>
                <td class="value">
                    {{ ($technical_sheet->wash_tone->settings->code ?? 'No Aplica') . ' - ' . ($technical_sheet->wash_tone->name ?? '-') }}
                </td>
            </tr>

            <tr>
                @php
                    $status_technical_sheet = $technical_sheet->status ?? null;
                    switch ($status_technical_sheet) {
                        case 'Aprobado':
                            $statusBg = '#ECFDF5';
                            $statusColor = '#059669';
                            break;

                        case 'Pendiente':
                            $statusBg = '#FFFBEB';
                            $statusColor = '#D97706';
                            break;

                        case 'En revision':
                            $statusBg = '#DBEAFE';
                            $statusColor = '#2563EB';
                            break;

                        default:
                            $statusBg = '#F3F4F6';
                            $statusColor = '#6B7280';
                            $status = 'Sin estado';
                            break;
                    }
                @endphp

                <td class="label">Tipo prenda</td>
                <td class="value">{{ $technical_sheet->garment_type->name ?? '-' }}</td>

                <td class="label">Tipo Bota</td>
                <td class="value">{{ $technical_sheet->boot_type->name ?? '-' }}</td>

                <td class="label">Estado</td>
                <td class="value"
                    style="background: {{ $statusBg }};color: {{ $statusColor }};text-align:center;font-weight:bold;text-transform:uppercase">
                    {{ $status_technical_sheet }}
                </td>
            </tr>

            <tr>
                <td class="label">Tipo Trasero</td>
                <td class="value">{{ $technical_sheet->back_type->name ?? '-' }}</td>

                <td class="label">Tipo Pretina</td>
                <td class="value">{{ $technical_sheet->waistband_type->name ?? '-' }}</td>

                <td class="label">Tipo Cotilla</td>
                <td class="value">{{ $technical_sheet->yoke_type->name ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Medida Pretina</td>
                <td class="value">{{ $technical_sheet->measure_of_waistband ?? '-' }}</td>

                <td class="label">N° de Botones</td>
                <td class="value">{{ $technical_sheet->number_of_buttons ?? '-' }}</td>

                <td class="label">Muestra Fisica</td>
                <td class="value">
                    {{ $technical_sheet->physical_sample ? 'Sí' : 'No' }}
                </td>

            </tr>

            <tr>
                <td class="label">Patronista</td>
                <td class="value" colspan="5" style="text-transform: uppercase;">
                    {{ ($technical_sheet->pattern_maker->person->document ?? '-') . ' - ' . ($technical_sheet->pattern_maker->person->names ?? '-') . ' ' . ($technical_sheet->pattern_maker->person->last_names ?? '-') . ' - ' . (optional($technical_sheet->pattern_maker->position)->name ?? 'No Aplica') }}
                </td>
            </tr>

            <tr>
                <td class="label">Descripción</td>
                <td class="value" colspan="5">{{ $technical_sheet->description ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Observación</td>
                <td class="value" colspan="5">{{ $technical_sheet->observation ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label-primary" colspan="3">Foto Delantera</td>
                <td class="label-primary" colspan="3">Foto Trasera</td>
            </tr>

            <tr>
                <td colspan="3" class="image-cell">
                    @if ($technical_sheet->photo_d)
                        <img src="{{ $technical_sheet->photo_d->pdf_path }}" class="product-image">
                    @endif
                </td>

                <td colspan="3" class="image-cell">
                    @if ($technical_sheet->photo_t)
                        <img src="{{ $technical_sheet->photo_t->pdf_path }}" class="product-image">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        @php
            $detail = $production_order->production_order_details->first(
                fn($item) => $item->model_type === Supply::class,
            );

            $grouped = $detail ? $detail->production_order_detail_quantities->groupBy('size_id') : collect();

            $rows = $grouped->count() ? $grouped->max(fn($items) => $items->count()) : 0;

        @endphp

        <table class="info-table">

            @php
                $total_columnas = $production_order->technical_sheet->product->trademark->sizes->count() + 5;
            @endphp

            <tr>
                <td class="section-title" colspan="{{ $total_columnas }}">
                    ESPECIFICACIONES DE CURVA
                </td>
            </tr>

            <tr>
                <td class="label" style="width: 20%;text-align:center;">Lote</td>
                <td class="value" style="color:#0a40f0; width: 20%;font-size:14px;text-align:center;"
                    colspan="3">
                    <strong>{{ $production_order->cut ?? '-' }}</strong>
                </td>

                <td class="label-primary"
                    colspan="{{ count($production_order->technical_sheet->product->trademark->sizes) + 1 }}">Tallas
                </td>
            </tr>

            <tr>
                <td class="label-primary">Tela</td>
                <td class="label-primary" colspan="2">Nombre</td>
                <td class="label-primary">Color</td>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    <td class="label-primary"
                        @if ($loop->last) style="border-right:1px solid #9CA3AF;" @endif>
                        {{ $size->name }}
                    </td>
                @endforeach
                <td class="label-primary" style="width: 10%;border-right:0.25px solid rgb(0, 0, 0);">Total</td>
            </tr>

            @for ($i = 0; $i < $rows; $i++)
                <tr>
                    <td class="value-primary">{{ $i + 1 }}</td>

                    @if ($i == 0)
                        <td class="value-primary" rowspan="{{ $rows }}" colspan="2">
                            {{ $production_order->fabric->model->name ?? '-' }} -
                            {{ $production_order->fabric->model->description ?? '-' }}
                        </td>

                        <td class="value-primary" rowspan="{{ $rows }}">
                            {{ optional($production_order->color->first())->settings->code ?? '-' }} -
                            {{ optional($production_order->color->first())->name ?? '-' }}
                        </td>
                    @endif

                    @php
                        $total_fila = 0;
                    @endphp

                    @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                        @php
                            $cantidad = optional(($grouped->get($size->id) ?? collect())->get($i))->quantity ?? 0;
                            $total_fila += $cantidad;
                        @endphp
                        <td class="value-primary">{{ $cantidad }}</td>
                    @endforeach

                    <td class="value-primary total-column">{{ $total_fila }}</td>
                </tr>
            @endfor

            <tr>
                <td class="label-primary" colspan="4">TOTAL PRODUCIDO</td>

                @php
                    $gran_total = 0;
                @endphp

                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $total = ($grouped->get($size->id) ?? collect())->sum('quantity') ?? 0;
                        $gran_total += $total;
                    @endphp

                    <td class="total-row-value">{{ $total }}</td>
                @endforeach

                <td class="total-row-value">{{ $gran_total }}</td>
            </tr>

            <tr>
                <td class="label" colspan="2">Tela bolsillo</td>
                <td class="value" colspan="2">{{ $production_order->pocket_fabric ? 'Sí' : 'No' }}</td>

                @php
                    $total_columnas = $production_order->technical_sheet->product->trademark->sizes->count() + 1;
                @endphp

                <td class="label-primary" colspan="{{ $total_columnas }}">OBSERVACIÓN</td>
            </tr>

            <tr>
                @php
                    $metros_reales = collect($production_order->settings)->sum(
                        fn($item) => $item->large * $item->quantity,
                    );

                    $detail = $production_order->production_order_details->first(
                        fn($item) => $item->model_type === Supply::class,
                    );

                    $total_unidades = $detail
                        ? $detail->production_order_detail_quantities
                            ->groupBy('quantity')
                            ->map(fn($items) => $items->count())
                            ->reduce(function ($total, $cantidad, $quantity) {
                                return $total + $quantity * $cantidad;
                            }, 0)
                        : 0;

                    $promedio = $total_unidades > 0 ? $metros_reales / $total_unidades : 0;

                    $total_columnas = $production_order->technical_sheet->product->trademark->sizes->count() + 1;

                @endphp

                <td class="label" colspan="2">Promedio Tela</td>
                <td class="value" colspan="2">{{ number_format($promedio, 3, ',', '.') }}</td>

                <td class="value-primary" rowspan="2" colspan="{{ $total_columnas }}">
                    {{ $production_order->observation ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label" colspan="2">Total Piezas</td>
                <td class="value" colspan="2">{{ count($production_order->pieces) ?? '0' }}</td>
            </tr>
        </table>
    </div>

    <br /> <br />

    <div class="section">
        <table class="info-table">
            @php
                $total_columnas_dis = $production_order->technical_sheet->product->trademark->sizes->count() + 4;
            @endphp

            <tr>
                <td class="section-title" colspan="{{ $total_columnas_dis }}">
                    DISTRIBUCIÓN DE CURVA ORIGINAL
                </td>
            </tr>

            <tr>
                <td class="label-primary" rowspan="2">Destino</td>
                <td class="label-primary-auxref" rowspan="2" colspan="2">Referencia</td>
                <td class="label-primary"
                    colspan="{{ count($production_order->technical_sheet->product->trademark->sizes) }}">Tallas</td>
                <td class="label-primary" rowspan="2">Total</td>
            </tr>

            <tr>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    <td class="label-primary"
                        @if ($loop->last) style="border-right:1px solid #9CA3AF;" @endif>
                        {{ $size->name }}
                    </td>
                @endforeach
            </tr>

            <tr>
                @php
                    $detail_nacional = $production_order->production_order_details->first(
                        fn($item) => $item->destination === 'NACIONAL',
                    );
                @endphp
                <td class="label-primary">NACIONAL</td>
                <td class="value-primary" colspan="2">{{ $detail_nacional->model?->code }}</td>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $item = $detail_nacional
                            ? $detail_nacional->production_order_detail_quantities->first(
                                fn($item) => $item->size_id === $size->id,
                            )
                            : null;
                    @endphp
                    <td class="value-primary row-national">{{ $item?->quantity ?? 0 }}</td>
                @endforeach
                <td class="value-primary total-column">
                    {{ $detail_nacional ? $detail_nacional->production_order_detail_quantities->sum('quantity') : 0 }}
                </td>
            </tr>

            <tr>
                @php
                    $detail_medellin = $production_order->production_order_details->first(
                        fn($item) => $item->destination === 'MEDELLIN',
                    );
                @endphp
                <td class="label-primary">MEDELLIN</td>
                <td class="value-primary" colspan="2">{{ $detail_medellin?->model?->code ?? '-' }}</td>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $item = $detail_medellin
                            ? $detail_medellin->production_order_detail_quantities->first(
                                fn($item) => $item->size_id === $size->id,
                            )
                            : null;
                    @endphp
                    <td class="value-primary row-medellin">{{ $item?->quantity ?? 0 }}</td>
                @endforeach
                <td class="value-primary total-column">
                    {{ $detail_medellin ? $detail_medellin->production_order_detail_quantities->sum('quantity') : 0 }}
                </td>
            </tr>

            <tr>
                @php
                    $detail_stara = $production_order->production_order_details->first(
                        fn($item) => $item->destination === 'STARA',
                    );
                @endphp
                <td class="label-primary">STARA</td>
                <td class="value-primary" colspan="2">{{ $detail_stara?->model?->code ?? '-' }}</td>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $item = $detail_stara
                            ? $detail_stara->production_order_detail_quantities->first(
                                fn($item) => $item->size_id === $size->id,
                            )
                            : null;
                    @endphp
                    <td class="value-primary row-stara">{{ $item?->quantity ?? 0 }}</td>
                @endforeach
                <td class="value-primary total-column">
                    {{ $detail_stara ? $detail_stara->production_order_detail_quantities->sum('quantity') : 0 }}
                </td>
            </tr>

            <tr>
                <td class="label-primary" colspan="3">CURVA TOTAL</td>
                @php
                    $gran_total = 0;
                @endphp
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $total = $production_order->production_order_details
                            ->filter(fn($detail) => $detail->model_type === Product::class)
                            ->sum(function ($detail) use ($size) {
                                return $detail->production_order_detail_quantities->first(
                                    fn($item) => $item->size_id === $size->id,
                                )?->quantity ?? 0;
                            });
                        $gran_total += $total;
                    @endphp

                    <td class="value-primary row-total">{{ $total }}</td>
                @endforeach
                <td class="value-primary grand-total">{{ $gran_total }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="info-table">
            <tr>
                <td class="section-title" colspan="3">
                    TIPOS DE INSUMO
                </td>
            </tr>

            <tr>
                <td class="label-primary">Nombre</td>
                <td class="label-primary">Descripción</td>
                <td class="label-primary">Insumo</td>
            </tr>

            @foreach ($technical_sheet->supplies as $supply)
                <tr>
                    <td class="value-primary">{{ optional($supply->supply_type->first())->name ?? '-' }}</td>
                    <td class="value-primary">{{ optional($supply->supply_type->first())->description ?? '-' }}</td>
                    <td class="value-primary">{{ ($supply->name ?? '-') . ' - ' . ($supply->description ?? '-') }}
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <div class="section-title">PROCESOS</div>
    </div>

    @foreach ($processes as $process)
        <div class="section">
            @php
                $detail_process = $technical_sheet->technical_sheet_details->first(
                    fn($item) => $item->model_id === $process->id,
                );

                $status = $detail_process->status ?? null;

                switch ($status) {
                    case 'Aprobado':
                        $statusBg = '#ECFDF5';
                        $statusColor = '#059669';
                        break;

                    case 'Pendiente':
                        $statusBg = '#FFFBEB';
                        $statusColor = '#D97706';
                        break;

                    case 'En revision':
                        $statusBg = '#DBEAFE';
                        $statusColor = '#2563EB';
                        break;

                    default:
                        $statusBg = '#F3F4F6';
                        $statusColor = '#6B7280';
                        $status = 'No Asociado';
                        break;
                }

                $table_dinamic_process = data_get($detail_process, 'settings.dinamic');
                $table_static_process = data_get($detail_process, 'settings.static');
            @endphp
            <table class="info-table">
                <thead>
                    <tr>
                        <td class="label-primary" style="color: #000;" colspan="3">{{ $process->name ?? '-' }}
                        </td>
                        <td class="label-primary">Estado</td>
                        <td
                            style="background: {{ $statusBg }};color: {{ $statusColor }};text-align:center;font-weight:bold;text-transform:uppercase">
                            {{ $status }}
                        </td>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $has_dynamic_process =
                            isset($table_dinamic_process->body) && count($table_dinamic_process->body);

                        $has_dynamic_values =
                            isset($table_dinamic_process->values) && count($table_dinamic_process->values);

                        $has_static_process = isset($table_static_process->body) && count($table_static_process->body);

                        $has_static_values =
                            isset($table_static_process->values) && count((array) $table_static_process->values);

                        $has_process = $has_dynamic_process || $has_static_process;

                        $has_values = $has_dynamic_values || $has_static_values;
                    @endphp

                    @if (!$has_process)
                        <tr>
                            <td colspan="5" class="no-data">
                                ⚠ No hay información registrada para este proceso.
                            </td>
                        </tr>
                    @elseif (!$has_values)
                        <tr>
                            <td colspan="5" class="no-data">

                                @if ($status === 'Aprobado')
                                    ⚠ No hay información registrada para este proceso.
                                @else
                                    ⚠ Este proceso se encuentra en estado
                                    <b>{{ $status }}</b> y aún no se ha diligenciado la información
                                    correspondiente.
                                @endif

                            </td>
                        </tr>
                    @endif

                    @if ($has_dynamic_process && $has_dynamic_values)
                        <tr>
                            <td colspan="5">
                                @if (isset($table_dinamic_process->body) && count($table_dinamic_process->body))
                                    <table class="info-table">
                                        <tr>
                                            <td class="label-primary"
                                                colspan="{{ count($table_dinamic_process->body ?? []) }}">
                                                {{ $table_dinamic_process->header ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            @foreach ($table_dinamic_process->body ?? [] as $column)
                                                <td class="label-primary">{{ $column->label }}</td>
                                            @endforeach
                                        </tr>
                                        @foreach ($table_dinamic_process->values ?? [] as $row)
                                            <tr>
                                                @foreach ($table_dinamic_process->body ?? [] as $column)
                                                    <td class="value-primary">
                                                        {{ data_get($row, $column->field, '-') }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif
                            </td>
                        </tr>
                    @endif

                    @if ($has_static_process && $has_static_values)
                        <tr>
                            <td colspan="5">
                                @if (isset($table_static_process->body) && count($table_static_process->body))
                                    @php
                                        $static_columns = collect($table_static_process->body[0] ?? [])->sum(
                                            fn($item) => $item->colspan ?? 1,
                                        );
                                    @endphp

                                    <table class="info-table">
                                        <tr>
                                            <td class="label-primary" colspan="{{ $static_columns }}">
                                                {{ $table_static_process->header->label ?? '-' }}</td>
                                        </tr>

                                        @foreach ($table_static_process->body ?? [] as $row)
                                            <tr>
                                                @foreach ($row as $cell)
                                                    @if ($cell->cell == 'th')
                                                        <td class="label-primary-table"
                                                            colspan="{{ $cell->colspan ?? 1 }}"
                                                            rowspan="{{ $cell->rowspan ?? 1 }}">
                                                            {{ $cell->label ?? '-' }}
                                                        </td>
                                                    @else
                                                        <td class="value-primary" colspan="{{ $cell->colspan ?? 1 }}"
                                                            rowspan="{{ $cell->rowspan ?? 1 }}">
                                                            {{ data_get($table_static_process->values, $cell->field, '-') }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif
                            </td>
                        </tr>
                    @endif

                </tbody>
            </table>
        </div>

        @php
            $subprocesses = collect();

            foreach ($process->subprocesses as $subprocess) {
                $detail = $technical_sheet->technical_sheet_details->first(
                    fn($item) => $item->model_id === $subprocess->id,
                );

                if ($detail) {
                    $subprocess->detail = $detail;
                    $subprocesses->push($subprocess);
                }
            }
        @endphp

        @foreach ($subprocesses as $subprocess)
            <div class="section" style="margin-top:-1px;">
                @php
                    $detail_subprocess = $technical_sheet->technical_sheet_details->first(
                        fn($item) => $item->model_id === $subprocess->id,
                    );

                    $status = $detail_subprocess->status ?? null;

                    switch ($status) {
                        case 'Aprobado':
                            $statusBg = '#ECFDF5';
                            $statusColor = '#059669';
                            break;

                        case 'Pendiente':
                            $statusBg = '#FFFBEB';
                            $statusColor = '#D97706';
                            break;

                        case 'En revision':
                            $statusBg = '#DBEAFE';
                            $statusColor = '#2563EB';
                            break;

                        default:
                            $statusBg = '#F3F4F6';
                            $statusColor = '#6B7280';
                            $status = 'Sin estado';
                            break;
                    }

                    $table_dinamic_subprocess = data_get($detail_subprocess, 'settings.dinamic');
                    $table_static_subprocess = data_get($detail_subprocess, 'settings.static');

                @endphp
                <table class="info-table">
                    <thead>
                        <tr>
                            <td class="label-primary" style="color: #000;" colspan="3">
                                {{ $subprocess->name ?? '-' }}
                            </td>
                            <td class="label-primary">Estado</td>
                            <td
                                style="background: {{ $statusBg }};color: {{ $statusColor }};text-align:center;font-weight:bold;text-transform:uppercase">
                                {{ $status }}
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $has_dynamic_subprocess =
                                isset($table_dinamic_subprocess->body) && count($table_dinamic_subprocess->body);
                            $has_static_subprocess =
                                isset($table_static_subprocess->body) && count($table_static_subprocess->body);
                        @endphp

                        @if ($has_dynamic_subprocess)
                            <tr>
                                <td colspan="5">
                                    @if (isset($table_dinamic_subprocess->body) && count($table_dinamic_subprocess->body))
                                        <table class="info-table">
                                            <tr>
                                                <td class="label-primary"
                                                    colspan="{{ count($table_dinamic_subprocess->body ?? []) }}">
                                                    {{ $table_dinamic_subprocess->header ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                @foreach ($table_dinamic_subprocess->body ?? [] as $column)
                                                    <td class="label-primary">{{ $column->label }}</td>
                                                @endforeach
                                            </tr>
                                            @foreach ($table_dinamic_subprocess->values ?? [] as $row)
                                                <tr>
                                                    @foreach ($table_dinamic_subprocess->body ?? [] as $column)
                                                        <td class="value-primary">
                                                            {{ data_get($row, $column->field, '-') }}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        @if ($has_static_subprocess)
                            <tr>
                                <td colspan="5">
                                    @if (isset($table_static_subprocess->body) && count($table_static_subprocess->body))
                                        @php
                                            $static_columns = collect($table_static_subprocess->body[0] ?? [])->sum(
                                                fn($item) => $item->colspan ?? 1,
                                            );
                                        @endphp

                                        <table class="info-table">
                                            <tr>
                                                <td class="label-primary" colspan="{{ $static_columns }}">
                                                    {{ $table_static_subprocess->header->label ?? '-' }}</td>
                                            </tr>

                                            @foreach ($table_static_subprocess->body ?? [] as $row)
                                                <tr>
                                                    @foreach ($row as $cell)
                                                        @if ($cell->cell == 'th')
                                                            <td class="label-primary-table"
                                                                colspan="{{ $cell->colspan ?? 1 }}"
                                                                rowspan="{{ $cell->rowspan ?? 1 }}">
                                                                {{ $cell->label ?? '-' }}
                                                            </td>
                                                        @else
                                                            <td class="value-primary"
                                                                colspan="{{ $cell->colspan ?? 1 }}"
                                                                rowspan="{{ $cell->rowspan ?? 1 }}">
                                                                {{ data_get($table_static_subprocess->values, $cell->field, '-') }}
                                                            </td>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        @if (!$has_dynamic_subprocess && !$has_static_subprocess)
                            <tr>
                                <td colspan="5" class="no-data">
                                    ⚠ No hay información registrada para este proceso.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @php
                $operations = collect();

                foreach ($subprocess->operations as $operation) {
                    $detail = $technical_sheet->technical_sheet_details->first(
                        fn($item) => $item->model_id === $operation->id,
                    );

                    if ($detail) {
                        $operation->detail = $detail;
                        $operations->push($operation);
                    }
                }
            @endphp

            @foreach ($operations as $operation)
                <div class="section" style="margin-top:-1px;">
                    @php
                        $detail_operation = $technical_sheet->technical_sheet_details->first(
                            fn($item) => $item->model_id === $operation->id,
                        );

                        $status = $detail_operation->status ?? null;

                        switch ($status) {
                            case 'Aprobado':
                                $statusBg = '#ECFDF5';
                                $statusColor = '#059669';
                                break;

                            case 'Pendiente':
                                $statusBg = '#FFFBEB';
                                $statusColor = '#D97706';
                                break;

                            case 'En revision':
                                $statusBg = '#DBEAFE';
                                $statusColor = '#2563EB';
                                break;

                            default:
                                $statusBg = '#F3F4F6';
                                $statusColor = '#6B7280';
                                $status = 'Sin estado';
                                break;
                        }

                        $table_dinamic_operation = data_get($detail_operation, 'settings.dinamic');
                        $table_static_operation = data_get($detail_operation, 'settings.static');

                    @endphp
                    <table class="info-table">
                        <thead>
                            <tr>
                                <td class="label-primary" style="color: #000;" colspan="3">
                                    {{ $operation->name ?? '-' }}
                                </td>
                                <td class="label-primary">Estado</td>
                                <td
                                    style="background: {{ $statusBg }};color: {{ $statusColor }};text-align:center;font-weight:bold;text-transform:uppercase">
                                    {{ $status }}
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $has_dynamic_operation =
                                    isset($table_dinamic_operation->body) && count($table_dinamic_operation->body);
                                $has_static_operation =
                                    isset($table_static_operation->body) && count($table_static_operation->body);
                            @endphp

                            @if ($has_dynamic_operation)
                                <tr>
                                    <td colspan="5">
                                        @if (isset($table_dinamic_operation->body) && count($table_dinamic_operation->body))
                                            <table class="info-table">
                                                <tr>
                                                    <td class="label-primary"
                                                        colspan="{{ count($table_dinamic_operation->body ?? []) }}">
                                                        {{ $table_dinamic_operation->header ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    @foreach ($table_dinamic_operation->body ?? [] as $column)
                                                        <td class="label-primary">{{ $column->label }}</td>
                                                    @endforeach
                                                </tr>
                                                @foreach ($table_dinamic_operation->values ?? [] as $row)
                                                    <tr>
                                                        @foreach ($table_dinamic_operation->body ?? [] as $column)
                                                            <td class="value-primary">
                                                                {{ data_get($row, $column->field, '-') }}
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </table>
                                        @endif
                                    </td>
                                </tr>
                            @endif

                            @if ($has_static_operation)
                                <tr>
                                    <td colspan="5">
                                        @if (isset($table_static_operation->body) && count($table_static_operation->body))
                                            @php
                                                $static_columns = collect($table_static_operation->body[0] ?? [])->sum(
                                                    fn($item) => $item->colspan ?? 1,
                                                );
                                            @endphp

                                            <table class="info-table">
                                                <tr>
                                                    <td class="label-primary" colspan="{{ $static_columns }}">
                                                        {{ $table_static_operation->header->label ?? '-' }}</td>
                                                </tr>

                                                @foreach ($table_static_operation->body ?? [] as $row)
                                                    <tr>
                                                        @foreach ($row as $cell)
                                                            @if ($cell->cell == 'th')
                                                                <td class="label-primary-table"
                                                                    colspan="{{ $cell->colspan ?? 1 }}"
                                                                    rowspan="{{ $cell->rowspan ?? 1 }}">
                                                                    {{ $cell->label ?? '-' }}
                                                                </td>
                                                            @else
                                                                <td class="value-primary"
                                                                    colspan="{{ $cell->colspan ?? 1 }}"
                                                                    rowspan="{{ $cell->rowspan ?? 1 }}">
                                                                    {{ data_get($table_static_operation->values, $cell->field, '-') }}
                                                                </td>
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </table>
                                        @endif
                                    </td>
                                </tr>
                            @endif

                            @if (!$has_dynamic_operation && !$has_static_operation)
                                <tr>
                                    <td colspan="5" class="no-data">
                                        ⚠ No hay información registrada para este proceso.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endforeach
    @endforeach
</body>

</html>
