@php
    use App\Models\Product;
    use App\Models\Supply;
@endphp
@if (!empty($footer))
    {{-- Pie de página: plantilla de Chrome aparte que se repite en cada hoja; el controlador la pide con footer = true --}}
    <style>
        @foreach ([['Inter', 400, 'Inter-Regular'], ['Inter', '500 800', 'Inter-Bold'], ['Poppins', 400, 'Poppins-Regular'], ['Poppins', '500 800', 'Poppins-SemiBold']] as [$family, $weight, $file])
            @font-face {
                font-family: "{{ $family }}";
                font-weight: {{ $weight }};
                src: url("data:font/ttf;base64,{{ base64_encode(file_get_contents(storage_path('fonts/' . $file . '.ttf'))) }}") format("truetype");
            }
        @endforeach

        .kf {
            width: calc(100% - 40mm);
            margin: 0 20mm;
            border-top: 1.2px solid #334155;
            padding-top: 6px;
            display: flex;
            align-items: center;
            gap: 20px;
            font-family: "Inter", sans-serif;
            color: #0f172a;
            -webkit-print-color-adjust: exact;
        }

        .kf-meta {
            flex: 1;
            display: grid;
            grid-template-columns: auto auto;
            gap: 1px 22px;
            font-size: 7.5px;
            font-weight: 700;
        }

        .kf-meta span {
            display: inline-block;
            width: 78px;
            color: #64748b;
            font-size: 6.5px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
    </style>
    <div class="kf">
        <img src="{{ $kanri }}" style="height: 28px;">
        <div class="kf-meta">
            <div><span>Código</span>{{ $code ?? 'GP-FR-01' }}</div>
            <div><span>Versión</span>{{ $version ?? '01' }}</div>
            <div><span>Vigente desde</span>13/04/2026</div>
            <div><span>Impreso</span>{{ now()->format('d/m/Y · h:i a') }}</div>
        </div>
        <div style="font-size: 8.5px;">Página <b class="pageNumber"></b> de <b class="totalPages"></b></div>
    </div>
@else
@php
    // Orden de producción, impresa con Chrome (ChromePdfService) en A4 horizontal, con el diseño de las fichas
    // (css/pdf.styles.css): página 1 con la información del producto y de la orden, las cifras y las fotos;
    // después el origen y los derivados, la curva por destino y por tela, el largo por agrupación, los rollos y las piezas.
    // El pie va en este mismo archivo (rama footer); los trazos se pegan al final con FPDI en el controlador.
    $sheet = $production_order->technical_sheet;
    $join = fn(...$parts) => collect($parts)->filter(fn($part) => filled($part))->implode(' - ') ?: '-';
    $status_style = fn($status) => match ($status) {
        'Aprobado' => ['#ECFDF5', '#047857'],
        'Pendiente' => ['#FFFBEB', '#B45309'],
        'En revision' => ['#DBEAFE', '#1D4ED8'],
        default => ['#F3F4F6', '#6B7280'],
    };

    // Solo las tallas que tienen alguna cantidad en la orden (curva por destino o cantidades por tela)
    $used_size_ids = $production_order->production_order_details
        ->flatMap(fn($detail) => $detail->production_order_detail_quantities)
        ->filter(fn($quantity) => (float) $quantity->quantity != 0)
        ->pluck('size_id')
        ->unique();
    $visible_sizes = $sheet->product->trademark->sizes->filter(fn($size) => $used_size_ids->contains($size->id))->values();

    // Referencia = código + lote; con STARA también la referencia de esa marca
    $has_stara = $production_order->production_order_details->contains(fn($item) => $item->destination === 'STARA');
    $reference = collect([$sheet->product->code ?? null, $has_stara ? optional($sheet->products->first())->code : null])
        ->filter()
        ->map(fn($code) => $code . '-' . ($production_order->cut ?? '-'))
        ->implode(' · ') ?: '-';
    $collection = $join($sheet->collection->name ?? null, $sheet->collection->description ?? null);
    $color = $production_order->color->first();
    $fabric = $join($production_order->fabric->model->name ?? null, $production_order->fabric->model->description ?? null);
    $fabric_color = $join($color->settings->code ?? null, $color->name ?? null);

    // Información del producto con datos de la ficha técnica, para que la tarjeta no quede con pocos datos
    $product_info = [
        ['Grupo', optional($sheet->product->trademark->group->first())->name ?? '-'],
        ['Categoría', optional($sheet->product->subcategory->category->first())->name ?? '-'],
        ['Subcategoría', $sheet->product->subcategory->name ?? '-'],
        ['Subgrupo', $sheet->subgroup->name ?? '-'],
        ['Ficha técnica', $sheet->consecutive ?? '-'],
        ['Tipo prenda', $sheet->garment_type->name ?? '-'],
        ['Tipo bota', $sheet->boot_type->name ?? '-'],
        ['Tipo trasero', $sheet->back_type->name ?? '-'],
        ['Tipo pretina', $sheet->waistband_type->name ?? '-'],
        ['Tipo cotilla', $sheet->yoke_type->name ?? '-'],
        ['Tono lavado', $join($sheet->wash_tone->settings->code ?? null, $sheet->wash_tone->name ?? null)],
        ['Tela bolsillo', $production_order->pocket_fabric ? 'Sí' : 'No'],
    ];

    // Cifras de tela: metros reales de las agrupaciones (largo × cantidad), promedio por prenda y cm² por prenda
    $fabric_detail = $production_order->production_order_details->first(fn($item) => $item->model_type === Supply::class);
    $by_size = $fabric_detail ? $fabric_detail->production_order_detail_quantities->groupBy('size_id') : collect();
    $fabric_rows = $by_size->count() ? $by_size->max(fn($items) => $items->count()) : 0;
    $groupings_settings = collect($production_order->settings)->filter(fn($item) => isset($item->large));
    $real_meters = $groupings_settings->sum(fn($item) => (float) $item->large * (float) $item->quantity);
    $produced_units = $fabric_detail ? $fabric_detail->production_order_detail_quantities->sum('quantity') : 0;
    $average = $produced_units > 0 ? $real_meters / $produced_units : 0;
    $square_cm = (float) $production_order->width * $average * 10000;

    // Curva por destino y lo que se espera por talla (suma de los destinos)
    $destinations = collect(['NACIONAL', 'MEDELLIN', 'STARA'])->map(fn($destination) => [
        'name' => $destination,
        'detail' => $production_order->production_order_details->first(fn($item) => $item->destination === $destination),
    ]);
    $size_quantity = fn($detail, $size) => $detail?->production_order_detail_quantities->first(fn($item) => $item->size_id === $size->id)?->quantity ?? 0;
    $product_details = $production_order->production_order_details->filter(fn($detail) => $detail->model_type === Product::class);
    $expected = fn($size) => $product_details->sum(fn($detail) => $size_quantity($detail, $size));
    $produced = fn($size) => ($by_size->get($size->id) ?? collect())->sum('quantity');
    $difference_class = fn($value) => $value < 0 ? 'diff-negative' : ($value > 0 ? 'diff-positive' : 'diff-zero');

    // Largo por agrupación de curva: cada agrupación con su largo, sus tallas y su cantidad
    $groupings = $groupings_settings->map(fn($setting) => [
        'large' => $setting->large,
        'quantity' => $setting->quantity,
        'sizes' => ($fabric_detail ? $fabric_detail->production_order_detail_quantities->where('quantity', $setting->quantity) : collect())
            ->map(fn($item) => optional($visible_sizes->firstWhere('id', $item->size_id))->name)
            ->filter()
            ->sortBy(fn($name) => $visible_sizes->search(fn($size) => $size->name === $name))
            ->values(),
    ])->values();

    $total_meters = $production_order->rolls->sum(fn($roll) => (float) ($roll->model->settings->values->meters ?? 0));
    $total_used = $production_order->rolls->sum(fn($roll) => (float) ($roll->settings->quantity ?? 0));

    // Fotos en recuadros iguales (875×500, la proporción del espacio de cada foto): la foto completa va encima y lo que
    // sobra se llena con la misma foto ampliada y desenfocada, para que una vertical y una horizontal se vean parejas
    $photo_box = function ($file) {
        $source = $file ? @imagecreatefromstring((string) @file_get_contents($file->pdf_path)) : false;
        if (!$source) {
            return null;
        }
        [$box_width, $box_height] = [875, 500];
        $source_width = imagesx($source);
        $source_height = imagesy($source);
        $box = imagecreatetruecolor($box_width, $box_height);

        // Fondo: la foto cubriendo el recuadro, reducida, desenfocada varias veces y vuelta a ampliar (sin cuadritos)
        $cover = max($box_width / $source_width, $box_height / $source_height);
        $small = imagecreatetruecolor(175, 100);
        imagecopyresampled($small, $source, 0, 0, (int) (($source_width - $box_width / $cover) / 2), (int) (($source_height - $box_height / $cover) / 2), 175, 100, (int) ($box_width / $cover), (int) ($box_height / $cover));
        for ($i = 0; $i < 12; $i++) {
            imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);
        }
        imagecopyresampled($box, $small, 0, 0, 0, 0, $box_width, $box_height, 175, 100);
        for ($i = 0; $i < 4; $i++) {
            imagefilter($box, IMG_FILTER_GAUSSIAN_BLUR);
        }
        imagefilledrectangle($box, 0, 0, $box_width, $box_height, imagecolorallocatealpha($box, 255, 255, 255, 80));

        // Encima, la foto completa centrada
        $contain = min($box_width / $source_width, $box_height / $source_height);
        $width = (int) round($source_width * $contain);
        $height = (int) round($source_height * $contain);
        imagecopyresampled($box, $source, (int) (($box_width - $width) / 2), (int) (($box_height - $height) / 2), 0, 0, $width, $height, $source_width, $source_height);

        ob_start();
        imagejpeg($box, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        imagedestroy($small);
        imagedestroy($box);

        return 'data:image/jpeg;base64,' . base64_encode($jpeg);
    };
    [$status_bg, $status_color] = $status_style($production_order->status);
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $name }}</title>
    <style>
        @foreach ([['Inter', 400, 'Inter-Regular'], ['Inter', '500 800', 'Inter-Bold'], ['Poppins', 400, 'Poppins-Regular'], ['Poppins', '500 800', 'Poppins-SemiBold']] as [$family, $weight, $file])
            @font-face {
                font-family: "{{ $family }}";
                font-weight: {{ $weight }};
                src: url("file:///{{ ltrim(str_replace('\\', '/', storage_path('fonts/' . $file . '.ttf')), '/') }}") format("truetype");
            }
        @endforeach

        {!! file_get_contents(resource_path('css/pdf.styles.css')) !!}
    </style>
</head>

<body class="po">
    {{-- Página 1 a la altura de la hoja: a la izquierda la información y las cifras; a la derecha las fotos, que llenan el alto --}}
    <div class="po-page-one">
        <table class="kh">
            <tr>
                <td style="width: 22%;"><img src="{{ $logo }}" style="width: 105px;"></td>
                <td class="kh-title">ORDEN DE PRODUCCIÓN</td>
                <td style="width: 14%;"><img src="{{ $qr }}" style="width: 74px; height: 74px;"></td>
            </tr>
        </table>

        <div class="hero">
            <div class="hero-ref"><span>Referencia</span><b>{{ $reference }}</b></div>
            <div class="hero-side">
                <div><span>Marca</span><b>{{ $sheet->product->trademark->name ?? '-' }}</b></div>
                <div><span>Colección</span><b>{{ $collection }}</b></div>
            </div>
        </div>

        <div class="po-cols">
            {{-- Sin origen y derivados sobra espacio: los cuadros de información se agrandan --}}
            <div class="po-info {{ $sheet->model || $sheet->derivatives->isNotEmpty() || $production_order->reassignments->isNotEmpty() || $production_order->derivatives->isNotEmpty() ? '' : 'roomy' }}">
                {{-- Origen y derivados: solo si viene de una transformación, tiene transformaciones o se reasignó curva --}}
                @php
                    $order = $production_order ?? null;
                    $order_code = fn($item) => ($item?->technical_sheet?->product?->code ?? '-') . '-' . ($item?->cut ?? '-');
                    $origin_rows = [];
                    if ($sheet->model || $sheet->derivatives->isNotEmpty()) {
                        $origin_rows[] = ['Transformación', $sheet->model->product->code ?? '-', $sheet->derivatives->map(fn($derivative) => $derivative->product?->code ?? '-')->implode(', ') ?: '-'];
                    }
                    if ($order && ($order->reassignments->isNotEmpty() || $order->derivatives->isNotEmpty())) {
                        $origin_rows[] = [
                            'Reasignación',
                            $order->reassignments->map(fn($reassignment) => $order_code($reassignment->model))->implode(', ') ?: '-',
                            $order->derivatives->map(fn($derivative) => $order_code($derivative->production_order))->implode(', ') ?: '-',
                        ];
                    }
                @endphp
                @if ($origin_rows)
                    <div class="sec">
                        <div class="sec-title">Origen y derivados</div>
                        <div class="tcard">
                            <table class="t origin">
                                <thead>
                                    <tr>
                                        <th style="width: 20%;">-</th>
                                        <th>Origen</th>
                                        <th>Derivados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($origin_rows as [$label, $from, $to])
                                        <tr>
                                            <td class="lbl">{{ $label }}</td>
                                            <td class="code">{{ $from }}</td>
                                            <td class="code">{{ $to }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="sec">
                    <div class="sec-title">Información del producto</div>
                    <div class="grid po-grid">
                        @foreach ($product_info as [$label, $value])
                            <div class="kv"><span>{{ $label }}</span><b>{{ $value }}</b></div>
                        @endforeach
                    </div>
                </div>

                <div class="sec">
                    <div class="sec-title">Información de la orden de producción</div>
                    <div class="grid po-grid">
                        {{-- El lote ya va en la referencia --}}
                        <div class="kv"><span>N° orden</span><b>{{ $production_order->consecutive }}</b></div>
                        <div class="kv"><span>Estado</span><b class="badge" style="background: {{ $status_bg }}; color: {{ $status_color }};">{{ mb_strtoupper($production_order->status ?? '-') }}</b></div>
                        <div class="kv"><span>Eficiencia</span><b>{{ $production_order->efficiency }} %</b></div>
                        <div class="kv"><span>Ancho tela</span><b>{{ $production_order->width ?? '-' }}</b></div>
                        <div class="kv kv-span2"><span>Tela</span><b>{{ $fabric }}</b></div>
                        <div class="kv kv-span2"><span>Color</span><b>{{ $fabric_color }}</b></div>
                        <div class="kv"><span>Lugar de producción</span><b>{{ $production_order->production_place ?? '-' }}</b></div>
                        {{-- El satélite solo aplica a las órdenes de satélite; si no, la observación va al lado del lugar --}}
                        @if ($production_order->production_place === 'SATELITE')
                            <div class="kv kv-span3"><span>Satélite</span><b>{{ $production_order->supplier->name ?? 'Sin asignar' }}</b></div>
                            <div class="kv kv-wide"><span>Observación</span><b>{{ $production_order->observation ?? 'N/A' }}</b></div>
                        @else
                            <div class="kv kv-span3"><span>Observación</span><b>{{ $production_order->observation ?? 'N/A' }}</b></div>
                        @endif
                    </div>
                </div>

                <div class="metrics">
                    <div class="metric"><span>Metros reales</span><b>{{ rtrim(rtrim(number_format($real_meters, 3, ',', '.'), '0'), ',') ?: '0' }}</b></div>
                    <div class="metric"><span>Promedio</span><b>{{ number_format($average, 3, ',', '.') }}</b></div>
                    {{-- Más de 15.000 cm² por prenda se marca en rojo --}}
                    <div class="metric {{ $square_cm > 15000 ? 'alert' : '' }}"><span>Cantidad cm²</span><b>{{ number_format($square_cm, 3, ',', '.') }}</b></div>
                </div>
            </div>

            <div class="sec po-photos">
                <div class="sec-title">Fotos de la prenda</div>
                @foreach ([$sheet->photo_d, $sheet->photo_t] as $file)
                    <div class="photo-card">
                        @if ($src = $photo_box($file))
                            <img src="{{ $src }}">
                        @else
                            <div class="photo-empty"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @php
        ob_start();
    @endphp
    <div class="sec">
        <div class="sec-title">Especificación de curva</div>
        <div class="tcard">
            <table class="t sizes">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 14%;">Destino</th>
                        <th rowspan="2" style="width: 18%;">Referencia</th>
                        <th colspan="{{ $visible_sizes->count() }}">Tallas</th>
                        <th rowspan="2" style="width: 8%;">Total</th>
                    </tr>
                    <tr>
                        @foreach ($visible_sizes as $size)
                            <th>{{ $size->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($destinations as $destination)
                        <tr class="dest-{{ strtolower($destination['name']) }}">
                            <td class="name strong">{{ $destination['name'] }}</td>
                            <td>{{ $destination['detail']?->model?->code ?? '-' }}</td>
                            @foreach ($visible_sizes as $size)
                                <td>{{ $size_quantity($destination['detail'], $size) }}</td>
                            @endforeach
                            <td class="sum">{{ $destination['detail'] ? $destination['detail']->production_order_detail_quantities->sum('quantity') : 0 }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="2">Curva</td>
                        @foreach ($visible_sizes as $size)
                            <td>{{ $expected($size) }}</td>
                        @endforeach
                        <td>{{ $visible_sizes->sum(fn($size) => $expected($size)) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @php
        $blocks['curve'] = ob_get_clean();
        ob_start();
    @endphp
    <div class="sec">
        <div class="sec-title">Agrupación por tela</div>
        <div class="tcard">
            <table class="t sizes">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 17%;">Color</th>
                        <th rowspan="2" style="width: 25%;">Tela</th>
                        <th colspan="{{ $visible_sizes->count() }}">Tallas</th>
                        <th rowspan="2" style="width: 8%;">Total</th>
                    </tr>
                    <tr>
                        @foreach ($visible_sizes as $size)
                            <th>{{ $size->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < $fabric_rows; $i++)
                        <tr class="{{ $i > 0 ? 'cont' : '' }}">
                            @if ($i === 0)
                                <td rowspan="{{ $fabric_rows }}" class="name">{{ $fabric_color }}</td>
                                <td rowspan="{{ $fabric_rows }}" class="name">{{ $fabric }}</td>
                            @endif
                            @foreach ($visible_sizes as $size)
                                <td>{{ optional(($by_size->get($size->id) ?? collect())->get($i))->quantity ?? 0 }}</td>
                            @endforeach
                            <td class="sum">{{ $visible_sizes->sum(fn($size) => optional(($by_size->get($size->id) ?? collect())->get($i))->quantity ?? 0) }}</td>
                        </tr>
                    @endfor
                    <tr class="total">
                        <td colspan="2">Total</td>
                        @foreach ($visible_sizes as $size)
                            <td>{{ $produced($size) }}</td>
                        @endforeach
                        <td>{{ $produced_units }}</td>
                    </tr>
                    {{-- Diferencia = producido − curva: negativa en rojo, positiva en azul y en cero en verde --}}
                    <tr>
                        <td colspan="2" class="lbl-diff">Diferencia</td>
                        @foreach ($visible_sizes as $size)
                            @php
                                $difference = $produced($size) - $expected($size);
                            @endphp
                            <td class="{{ $difference_class($difference) }}">{{ $difference }}</td>
                        @endforeach
                        @php
                            $total_difference = $produced_units - $visible_sizes->sum(fn($size) => $expected($size));
                        @endphp
                        <td class="{{ $difference_class($total_difference) }}">{{ $total_difference }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @php
        $blocks['group'] = ob_get_clean();
        ob_start();
    @endphp
            <div class="sec">
                <div class="sec-title">Largo por agrupación de curva</div>
                <div class="tcard">
                    <table class="t sizes groupings">
                        <tr>
                            <td class="lbl">Largo</td>
                            @forelse ($groupings as $grouping)
                                <td colspan="{{ max(1, $grouping['sizes']->count()) }}" class="large">{{ $grouping['large'] }}</td>
                            @empty
                                <td>-</td>
                            @endforelse
                        </tr>
                        <tr>
                            <td class="lbl">Tallas</td>
                            @forelse ($groupings as $grouping)
                                @forelse ($grouping['sizes'] as $size_name)
                                    <td>{{ $size_name }}</td>
                                @empty
                                    <td>-</td>
                                @endforelse
                            @empty
                                <td>-</td>
                            @endforelse
                        </tr>
                        <tr>
                            <td class="lbl">Cantidad</td>
                            @forelse ($groupings as $grouping)
                                <td colspan="{{ max(1, $grouping['sizes']->count()) }}" class="quantity">{{ $grouping['quantity'] }}</td>
                            @empty
                                <td>-</td>
                            @endforelse
                        </tr>
                    </table>
                </div>
                <div class="tcard" style="margin-top: 8px;">
                    <table class="t sizes">
                        <thead>
                            <tr>
                                @foreach ($visible_sizes as $size)
                                    <th>{{ $size->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @foreach ($visible_sizes as $size)
                                    <td class="sum">{{ $produced($size) }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

    @php
        $blocks['large'] = ob_get_clean();
        ob_start();
    @endphp
            <div class="sec">
                <div class="sec-title">Rollos de tela a utilizar</div>
                <div class="tcard">
                    <table class="t sizes">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Tela</th>
                                <th>Rollo</th>
                                <th>Ancho</th>
                                <th>Metros</th>
                                <th>Utilizados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($production_order->rolls as $roll)
                                <tr>
                                    <td class="name">{{ $roll->model->related(Supply::class)->first()?->name ?? '-' }}</td>
                                    <td>{{ $roll->model->name ?? '-' }}</td>
                                    <td>{{ $roll->model->settings->values->width ?? '-' }}</td>
                                    <td>{{ $roll->model->settings->values->meters ?? '-' }}</td>
                                    <td>{{ $roll->settings->quantity ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="color: #94a3b8;">Sin rollos</td>
                                </tr>
                            @endforelse
                            <tr class="total">
                                <td colspan="3">Total</td>
                                <td>{{ number_format($total_meters, 1, ',', '.') }}</td>
                                <td>{{ number_format($total_used, 1, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
    @php
        $blocks['rolls'] = ob_get_clean();
        ob_start();
    @endphp

        <div class="sec po-pieces">
            <div class="sec-title">Piezas</div>
            <div class="tcard">
                <table class="t sizes">
                    <thead>
                        <tr>
                            <th>Pieza</th>
                            <th style="width: 30%;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($production_order->pieces as $piece)
                            <tr>
                                <td class="name">{{ $piece->model->name ?? '-' }}</td>
                                <td>{{ $piece->settings->quantity ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="color: #94a3b8;">Sin piezas</td>
                            </tr>
                        @endforelse
                        <tr class="total">
                            <td>Total</td>
                            <td>{{ $production_order->pieces->sum(fn($piece) => $piece->settings->quantity ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @php
        $pieces_block = ob_get_clean();

        // Piezas se acomoda desde abajo, a la derecha de la página 2. Las secciones que caben completas por encima de
        // donde empieza piezas van a lo ancho; desde la primera que no cabe, van a la izquierda con piezas al lado.
        // Altos estimados en px (título 27, encabezado 28 o 46 con tallas, fila 23,5 medida en el PDF, espacio entre secciones 18) sobre
        // los 658 px útiles de la hoja horizontal
        $row = 23.5;
        $heights = [
            'curve' => 27 + 46 + 4 * $row,
            'group' => 27 + 46 + ($fabric_rows + 2) * $row,
            'large' => 27 + 3 * $row + 8 + 28 + $row,
            'rolls' => 27 + 28 + (max(1, $production_order->rolls->count()) + 1) * $row,
        ];
        $pieces_height = 27 + 28 + (max(1, $production_order->pieces->count()) + 1) * $row;
        $pieces_top = 658 - $pieces_height;
        // La especificación de curva siempre va a lo ancho
        $full = ['curve'];
        $used = $heights['curve'] + 18;
        foreach (array_slice($heights, 1, null, true) as $key => $height) {
            if ($used + $height > $pieces_top) {
                break;
            }
            $full[] = $key;
            $used += $height + 18;
        }
        $side = array_diff(array_keys($heights), $full);
        // Si piezas cabe en lo que queda de la hoja, se baja hasta el final (20 px de margen) y no se parte; si no cabe,
        // empieza arriba y sigue en la hoja siguiente con su encabezado
        $pieces_fit = $used + $pieces_height <= 658;
        $pieces_offset = $side && $pieces_fit ? max(0, $pieces_top - $used - 20) : 0;
    @endphp

    @foreach ($full as $key)
        {!! $blocks[$key] !!}
    @endforeach
    {{-- Piezas flota a la derecha (bajada hasta el final de la hoja y sin partirse si cabe); lo demás va a su izquierda con el
         mismo ancho en todas las hojas, así una tabla larga (rollos) puede seguir en la siguiente sin desacomodarse --}}
    <div class="po-split">
        <div class="po-pieces-wrap {{ $pieces_fit ? 'keep' : '' }}" style="margin-top: {{ $pieces_offset }}px;">{!! $pieces_block !!}</div>
        @foreach ($side as $key)
            {!! $blocks[$key] !!}
        @endforeach
    </div>
</body>

</html>
@endif
