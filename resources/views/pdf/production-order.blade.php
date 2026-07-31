@php
    use App\Models\Variant;
    use App\Models\Product;
@endphp

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    @include('pdf.styles')

</head>

<body>

    <table class="header-container">
        <tr>
            <td class="logo-cell" rowspan="3">
                @if (file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}">
                @else
                    <div class="logo-placeholder">BLESS</div>
                @endif
            </td>

            <td class="title-primary" rowspan="2" colspan="2">
                GESTIÓN DE PRODUCCIÓN
            </td>

            <td class="meta-cell">
                GP-FR-05 Versión 02
            </td>

            <td class="qr-cell" rowspan="3"></td>
        </tr>

        <tr>
            <td class="meta-cell">
                Vigente desde 13/04/2026
            </td>
        </tr>

        <tr>
            <td class="title-primary" colspan="2">
                ORDEN DE PRODUCCIÓN
            </td>
            <td class="meta-cell">
                Impreso:{{ now()->format('d/m/Y h:i A') }}
            </td>
        </tr>

    </table>

    <div class="section">
        <div class="section-title">
            INFORMACIÓN PRODUCTO
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Referencia</td>
                <td class="value">{{ $production_order->technical_sheet->product->code ?? '-' }}</td>

                <td class="label">Marca</td>
                <td class="value">{{ $production_order->technical_sheet->product->trademark->name ?? '-' }}</td>

                <td class="label">Grupo</td>
                <td class="value">
                    {{ optional($production_order->technical_sheet->product->trademark->group->first())->name ?? '-' }}
                </td>
            </tr>

            <tr>
                <td class="label">Categoría</td>
                <td class="value">
                    {{ optional($production_order->technical_sheet->product->subcategory->category->first())->name ?? '-' }}
                </td>

                <td class="label">Subcategoría</td>
                <td class="value">{{ $production_order->technical_sheet->product->subcategory->name ?? '-' }}</td>

                <td class="label">Tipo prenda</td>
                <td class="value">{{ $production_order->technical_sheet->garment_type->name ?? '-' }}</td>

            <tr>

            <tr>
                <td class="label">Subgrupo</td>
                <td class="value">{{ $production_order->technical_sheet->subgroup->name ?? '-' }}</td>

                <td class="label">Colección</td>
                <td class="value">
                    {{ ($production_order->technical_sheet->collection->settings->code ?? '-') . ' - ' . ($production_order->technical_sheet->collection->name ?? '-') }}
                </td>

                <td class="label">Tela bolsillo</td>
                <td class="value">
                    {{ $production_order->pocket_fabric ? 'Sí' : 'No' }}
                </td>
            </tr>

            <tr>
                <td class="label-primary" colspan="3">Foto Delantera</td>
                <td class="label-primary" colspan="3">Foto Trasera</td>
            </tr>

            <tr>
                <td colspan="3" class="image-cell">
                    @if ($production_order->technical_sheet->photo_d)
                        <img src="{{ $production_order->technical_sheet->photo_d->pdf_path }}" class="product-image">
                    @endif
                </td>

                <td colspan="3" class="image-cell">
                    @if ($production_order->technical_sheet->photo_t)
                        <img src="{{ $production_order->technical_sheet->photo_t->pdf_path }}" class="product-image">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">
            INFORMACIÓN ORDEN DE PRODUCCIÓN
        </div>

        <table class="info-table">
            <tr>
                <td class="label">N° Orden</td>
                <td class="value">{{ $production_order->consecutive }}</td>

                <td class="label">Lote</td>
                <td class="value">{{ $production_order->cut ?? '-' }}</td>

                <td class="label">Fecha</td>
                <td class="value">{{ $production_order->date->format('d/m/Y') ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Tela</td>
                <td class="value" colspan="2">
                    {{ ($production_order->fabric->model->name ?? '-') . ' - ' . ($production_order->fabric->model->description ?? '-') }}
                </td>

                <td class="label">Color</td>
                <td class="value" colspan="2">
                    {{ (optional($production_order->color->first())->settings->code ?? '-') . '-' . (optional($production_order->color->first())->name ?? '-') }}
                </td>
            </tr>

            <tr>
                <td class="label">Ancho Tela</td>
                <td class="value">{{ $production_order->width }}</td>

                <td class="label">N° Piezas</td>
                <td class="value">{{ count($production_order->pieces) ?? '0' }}</td>

                <td class="label">Eficiencia</td>
                <td class="value">{{ $production_order->efficiency }} %</td>
            </tr>

            <tr>
                <td class="label">Observación</td>
                <td class="value" colspan="5">{{ $production_order->observation }}</td>
            </tr>

            <tr>
                <td class="label-primary" colspan="2">Metros Reales</td>
                <td class="label-primary" colspan="2">Promedio</td>
                <td class="label-primary" colspan="2">Cantidad CM²</td>
            </tr>

            <tr>
                @php
                    $metros_reales = collect($production_order->settings)->sum(
                        fn($item) => $item->large * $item->quantity,
                    );

                    $detail = $production_order->production_order_details->first(
                        fn($item) => $item->model_type === Variant::class,
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
                @endphp

                <td colspan="2" class="metric-box">
                    <div class="metric-value">
                        {{ number_format($metros_reales, 3, ',', '.') }}
                    </div>
                </td>

                <td colspan="2" class="metric-box">
                    <div class="metric-value">
                        {{ number_format($promedio, 3, ',', '.') }}
                    </div>
                </td>

                <td colspan="2" class="metric-box">
                    <div class="metric-value">
                        {{ number_format($production_order->width * $promedio * 10000, 3, ',', '.') }}
                    </div>
                </td>
            </tr>

        </table>

    </div>

    <div class="section">
        <div class="section-title">
            ESPECIFICACIÓN DE CURVA
        </div>

        <table class="info-table">
            <tr>
                <td class="label-primary" rowspan="2" style="width: 10%;">-</td>
                <td class="label-primary" rowspan="2" style="width: 20%;">Referencia</td>
                <td class="label-primary"
                    colspan="{{ count($production_order->technical_sheet->product->trademark->sizes) }}">Tallas</td>
                <td class="label-primary" rowspan="2" style="width: 10%;">Total</td>
            </tr>

            <tr>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    <td class="label-primary"
                        @if ($loop->last) style="border-right: 1px solid #000;" @endif>
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
                <td class="value-primary">{{ $detail_nacional->model?->code }}</td>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $item = $detail_nacional
                            ? $detail_nacional->production_order_detail_quantities->first(
                                fn($item) => $item->size_id === $size->id,
                            )
                            : null;
                    @endphp
                    <td class="value-primary">{{ $item?->quantity ?? 0 }}</td>
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
                <td class="value-primary">{{ $detail_medellin?->model?->code ?? '-' }}</td>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $item = $detail_medellin
                            ? $detail_medellin->production_order_detail_quantities->first(
                                fn($item) => $item->size_id === $size->id,
                            )
                            : null;
                    @endphp
                    <td class="value-primary">{{ $item?->quantity ?? 0 }}</td>
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
                <td class="value-primary">{{ $detail_stara?->model?->code ?? '-' }}</td>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $item = $detail_stara
                            ? $detail_stara->production_order_detail_quantities->first(
                                fn($item) => $item->size_id === $size->id,
                            )
                            : null;
                    @endphp
                    <td class="value-primary">{{ $item?->quantity ?? 0 }}</td>
                @endforeach
                <td class="value-primary total-column">
                    {{ $detail_stara ? $detail_stara->production_order_detail_quantities->sum('quantity') : 0 }}
                </td>
            </tr>

            <tr>
                <td class="label-primary" colspan="2">CURVA TOTAL</td>
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

                    <td class="value-primary curve-total">{{ $total }}</td>
                @endforeach
                <td class="value-primary grand-total">{{ $gran_total }}</td>
            </tr>

        </table>

    </div>

    <table class="two-tables-container">
        <tr>
            <td class="table-column-70" style="padding-right: 5px;">
                <div class="section-title">ROLLOS DE TELA A UTILIZAR</div>
                <table class="info-table">
                    <tr>
                        <td class="label-primary" rowspan="2" style="width: 30%;">TELA</td>
                        <td class="label-primary" colspan="4">ROLLO</td>
                    </tr>

                    <tr>
                        <td class="label-primary">NOMBRE</td>
                        <td class="label-primary">ANCHO</td>
                        <td class="label-primary">METROS</td>
                        <td class="label-primary">UTILIZADOS</td>
                    </tr>

                    @php
                        $total_metros = $production_order->rolls->sum(
                            fn($roll) => (float) ($roll->settings->values->meters ?? 0),
                        );

                        $total_utilizados = $production_order->rolls->sum(
                            fn($roll) => (float) ($roll->pivot->quantity ?? 0),
                        );
                    @endphp

                    @foreach ($production_order->rolls as $roll)
                        <tr>
                            @php
                                $fabric = $roll->related(Variant::class)->first();
                            @endphp
                            <td class="value-primary">
                                {{ ($fabric?->name ?? '-') . ' - ' . ($fabric?->description ?? '-') }}
                            </td>

                            <td class="value-primary">
                                {{ $roll->name ?? '-' }}
                            </td>

                            <td class="value-primary">
                                {{ $roll->settings->values->width ?? '-' }}
                            </td>

                            <td class="value-primary">
                                {{ $roll->settings->values->meters ?? '-' }}
                            </td>

                            <td class="value-primary">
                                {{ $roll->pivot->quantity ?? '-' }}
                            </td>
                        </tr>
                    @endforeach

                    <tr>
                        <td class="label-primary" colspan="3">
                            TOTAL
                        </td>

                        <td class="value-primary">
                            <strong>{{ number_format($total_metros, 1, ',', '.') }}</strong>
                        </td>

                        <td class="value-primary">
                            <strong>{{ number_format($total_utilizados, 1, ',', '.') }}</strong>
                        </td>
                    </tr>
                </table>
            </td>

            <td class="table-column-30" style="padding-left: 5px;">
                <div class="section-title">PIEZAS</div>
                <table class="info-table">
                    <tr>
                        <td class="label-primary">PIEZA</td>
                        <td class="label-primary">CANTIDAD</td>
                    </tr>

                    @foreach ($production_order->pieces as $piece)
                        <tr>
                            <td class="value-primary">
                                {{ $piece->name ?? '-' }}
                            </td>

                            <td class="value-primary">
                                {{ $piece->pivot->quantity ?? '-' }}
                            </td>
                        </tr>
                    @endforeach

                    <tr>
                        <td class="label-primary">TOTAL</td>
                        @php
                            $item = $production_order->pieces
                                ? $production_order->pieces->sum(fn($item) => $item->pivot->quantity)
                                : null;
                        @endphp
                        <td class="value-primary"><strong>{{ $item }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">
            AGRUPACIÓN DE CURVA
        </div>

        @php
            $detail = $production_order->production_order_details->first(
                fn($item) => $item->model_type === Variant::class,
            );

            $grouped = $detail ? $detail->production_order_detail_quantities->groupBy('size_id') : collect();

            $rows = $grouped->count() ? $grouped->max(fn($items) => $items->count()) : 0;

        @endphp

        <table class="info-table">
            <tr>
                <td class="label-primary-aux" style="width:23%; border-right:none;">
                    Tela
                </td>

                <td class="label-primary" style="width:15%; border-left:none;">
                </td>
                <td class="label-primary"
                    colspan="{{ count($production_order->technical_sheet->product->trademark->sizes) }}">Tallas</td>
                <td class="label-primary" rowspan="2" style="width: 10%;">Total</td>
            </tr>

            <tr>
                <td class="label-primary" style="width: 20%;">Nombre</td>
                <td class="label-primary" style="width: 20%;">Color</td>
                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    <td class="label-primary"
                        @if ($loop->last) style="border-right: 1px solid #000;" @endif>
                        {{ $size->name }}
                    </td>
                @endforeach
            </tr>

            @for ($i = 0; $i < $rows; $i++)
                <tr>
                    @if ($i == 0)
                        <td class="value-primary" rowspan="{{ $rows }}">
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

                        <td class="value-primary">
                            {{ $cantidad }}
                        </td>
                    @endforeach

                    <td class="value-primary total-column">
                        {{ $total_fila }}
                    </td>

                </tr>
            @endfor

            <tr>
                <td class="label-primary" colspan="2">TOTAL PRODUCIDO</td>

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
                <td class="label-primary" colspan="2">DIFERENCIA</td>

                @php
                    $gran_diferencia = 0;
                @endphp

                @foreach ($production_order->technical_sheet->product->trademark->sizes as $size)
                    @php
                        $producido = ($grouped->get($size->id) ?? collect())->sum('quantity') ?? 0;

                        $esperado = $production_order->production_order_details
                            ->filter(fn($detail) => $detail->model_type === Product::class)
                            ->sum(function ($detail) use ($size) {
                                return $detail->production_order_detail_quantities->first(
                                    fn($item) => $item->size_id == $size->id,
                                )?->quantity ?? 0;
                            });

                        $diferencia = $producido - $esperado;

                        $gran_diferencia += $diferencia;

                        $class = $diferencia < 0 ? 'diff-negative' : ($diferencia > 0 ? 'diff-positive' : 'diff-zero');

                    @endphp

                    <td class="{{ $class }}">{{ $diferencia }}</td>
                @endforeach

                @php
                    $classTotal =
                        $gran_diferencia < 0 ? 'diff-negative' : ($gran_diferencia > 0 ? 'diff-positive' : 'diff-zero');
                @endphp

                <td class="{{ $classTotal }}"> {{ $gran_diferencia }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">
            LARGO POR AGRUPACIÓN DE CURVA
        </div>

        @php
            $detail = $production_order->production_order_details->first(
                fn($item) => $item->model_type === Variant::class,
            );

            $sizes = $production_order->technical_sheet->product->trademark->sizes;
        @endphp

        <table class="info-table">

            <tr>
                <td class="label-primary" style="width:10%;">Largo </td>
                <td class="label-primary" style="width:10%;">Cantidad</td>
                <td class="label-primary">Tallas</td>
            </tr>

            @foreach (collect($production_order->settings) as $setting)
                @php

                    $items = $detail
                        ? $detail->production_order_detail_quantities->where('quantity', $setting->quantity)
                        : collect();

                    $tallas = $items->map(function ($item) use ($sizes) {
                        return optional($sizes->firstWhere('id', $item->size_id))->name;
                    });

                    $prendas = $items->sum('quantity');

                @endphp

                <tr>
                    <td class="value-primary">{{ $setting->large }}</td>
                    <td class="value-primary">{{ $setting->quantity }}</td>
                    <td class="value-primary">
                        @foreach ($tallas as $talla)
                            <span class="size-chip">
                                {{ $talla }}
                            </span>
                        @endforeach
                    </td>

                </tr>
            @endforeach
        </table>

        <table class="info-table" style="margin-top:-1px;">
            <tr>
                <td class="label-primary"
                    colspan="{{ count($production_order->technical_sheet->product->trademark->sizes) }}"> TOTAL POR
                    TALLA
                </td>
            </tr>

            <tr>
                @foreach ($sizes as $size)
                    <td class="label-primary">{{ $size->name }}</td>
                @endforeach
            </tr>

            <tr>
                @foreach ($sizes as $size)
                    @php
                        $total = $detail
                            ? $detail->production_order_detail_quantities->where('size_id', $size->id)->sum('quantity')
                            : 0;
                    @endphp
                    <td class="value-primary"><strong>{{ $total }}</strong></td>
                @endforeach
            </tr>
        </table>
    </div>

    {{-- Generar Grafico --}}
    {{-- @php
        $sizes = $production_order->technical_sheet->product->trademark->sizes;

        $labels = [];
        $esperado = [];
        $producido = [];

        foreach ($sizes as $size) {
            $labels[] = $size->name;

            $esperado[] = $production_order->production_order_details
                ->filter(fn($detail) => $detail->model_type === Product::class)
                ->sum(function ($detail) use ($size) {
                    return $detail->production_order_detail_quantities->first(fn($q) => $q->size_id == $size->id)
                        ?->quantity ?? 0;
                });

            $producido[] = $grouped->get($size->id, collect())->sum('quantity');
        }

        $todosLosValores = array_merge($esperado, $producido);
        $minVal = count($todosLosValores) ? min($todosLosValores) : 0;

        $yMin = $minVal > 0 ? $minVal - 1 : 0;

        $esperadoPointColors = [];
        $producidoPointColors = [];
        $colorConvergencia = '#8b5cf6';

        foreach ($esperado as $i => $valEsp) {
            $valProd = $producido[$i] ?? 0;

            if ($valEsp == $valProd && $valEsp > 0) {
                $esperadoPointColors[] = $colorConvergencia;
                $producidoPointColors[] = $colorConvergencia;
            } else {
                $esperadoPointColors[] = '#2563eb';
                $producidoPointColors[] = '#10b981';
            }
        }

        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Esperado',
                        'data' => $esperado,
                        'borderColor' => '#2563eb',
                        'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                        'fill' => true,
                        'tension' => 0.55,
                        'pointRadius' => 6,
                        'pointBackgroundColor' => $esperadoPointColors,
                        'pointBorderColor' => $esperadoPointColors,
                    ],
                    [
                        'label' => 'Producido',
                        'data' => $producido,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                        'fill' => true,
                        'tension' => 0.55,
                        'pointRadius' => 6,
                        'pointBackgroundColor' => $producidoPointColors,
                        'pointBorderColor' => $producidoPointColors,
                    ],
                ],
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                    ],
                ],
                'scales' => [
                    'x' => [
                        'grid' => [
                            'display' => false,
                        ],
                    ],
                    'y' => [
                        'min' => $yMin,
                        'grid' => [
                            'color' => '#f1f5f9',
                        ],
                    ],
                ],
            ],
        ];

        $chart = urlencode(json_encode($chartConfig));
        $urlChart = "https://quickchart.io/chart?width=900&height=350&c={$chart}";

    @endphp

    <div class="section">
        <div class="section-title">
            COMPARATIVO CURVA ESPERADA VS PRODUCIDA
        </div>

        <table class="info-table">
            <tr>
                <td style="padding:15px; text-align:center;">
                    <img src="{{ $urlChart }}" style="width:100%;">
                </td>
            </tr>
        </table>
    </div> --}}
</body>

</html>
