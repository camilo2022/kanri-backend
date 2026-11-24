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
    // Ficha técnica de una orden de producción, impresa con Chrome (ChromePdfService) y de corrido: página 1 con la
    // referencia, el origen y los derivados, la información, las fotos y los tipos de insumo; después la curva, su
    // distribución por destino y los procesos. Todo en este archivo, incluido el pie (rama footer).
    $sheet = $technical_sheet;

    // Solo las tallas que tienen alguna cantidad en la orden (curva por destino o cantidades por tela): las tallas en cero
    // en todas las filas no se muestran en ninguna tabla
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

    // Curva: cantidades por tela (una fila por tela, en el orden en que se guardaron) y por talla
    $fabric_detail = $production_order->production_order_details->first(fn($item) => $item->model_type === Supply::class);
    $by_size = $fabric_detail ? $fabric_detail->production_order_detail_quantities->groupBy('size_id') : collect();
    $fabric_rows = $by_size->count() ? $by_size->max(fn($items) => $items->count()) : 1;
    $real_meters = collect($production_order->settings)->sum(fn($item) => ($item->large ?? 0) * ($item->quantity ?? 0));
    $produced_units = $fabric_detail ? $fabric_detail->production_order_detail_quantities->sum('quantity') : 0;
    $color = $production_order->color->first();
    $fabric = ($production_order->fabric->model->name ?? '-') . ' - ' . ($production_order->fabric->model->description ?? '-');
    $fabric_color = ($color->settings->code ?? '-') . ' - ' . ($color->name ?? '-');

    // Distribución de la curva original por destino
    $destinations = collect(['NACIONAL', 'MEDELLIN', 'STARA'])->map(fn($destination) => [
        'name' => $destination,
        'detail' => $production_order->production_order_details->first(fn($item) => $item->destination === $destination),
    ]);
    $product_details = $production_order->production_order_details->filter(fn($detail) => $detail->model_type === Product::class);
    $size_quantity = fn($detail, $size) => $detail?->production_order_detail_quantities->first(fn($item) => $item->size_id === $size->id)?->quantity ?? 0;
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
                src: url("data:font/ttf;base64,{{ base64_encode(file_get_contents(storage_path('fonts/' . $file . '.ttf'))) }}") format("truetype");
            }
        @endforeach

        {!! file_get_contents(resource_path('css/pdf.styles.css')) !!}
    </style>
</head>

<body>
@php
    $join = fn(...$parts) => collect($parts)->filter(fn($part) => filled($part))->implode(' - ') ?: '-';
    $status_style = fn($status) => match ($status) {
        'Aprobado' => ['#ECFDF5', '#047857', 'Aprobado'],
        'Pendiente' => ['#FFFBEB', '#B45309', 'Pendiente'],
        'En revision' => ['#DBEAFE', '#1D4ED8', 'En revisión'],
        default => ['#F3F4F6', '#6B7280', 'No asociado'],
    };

    $collection = $join($sheet->collection->name ?? null, $sheet->collection->description ?? null);
    // Marca y colección ya van junto a la referencia; color y tono lavado, de a media fila
    $product_info = [
        ['Grupo', optional($sheet->product->trademark->group->first())->name ?? '-'],
        ['Categoría', optional($sheet->product->subcategory->category->first())->name ?? '-'],
        ['Subcategoría', $sheet->product->subcategory->name ?? '-'],
        ['Subgrupo', $sheet->subgroup->name ?? '-'],
        ['Color', $join($sheet->color->settings->code ?? null, $sheet->color->name ?? null)],
        ['Tono lavado', $join($sheet->wash_tone->settings->code ?? null, $sheet->wash_tone->name ?? null)],
    ];
    $person = $sheet->pattern_maker->person ?? null;
    $sheet_info = [
        ['Consecutivo', $sheet->consecutive ?? '-'],
        ['Estado', mb_strtoupper($sheet->status ?? '-')],
        ['Tipo prenda', $sheet->garment_type->name ?? '-'],
        ['Tipo bota', $sheet->boot_type->name ?? '-'],
        ['Tipo trasero', $sheet->back_type->name ?? '-'],
        ['Tipo pretina', $sheet->waistband_type->name ?? '-'],
        ['Tipo cotilla', $sheet->yoke_type->name ?? '-'],
        ['Medida pretina', $sheet->measure_of_waistband ?? '-'],
        ['N° de botones', $sheet->number_of_buttons ?? '-'],
        ['Muestra física', $sheet->physical_sample ? 'Sí' : 'No'],
    ];
    $pattern_maker_role = null;
    $pattern_maker = $person ? mb_strtoupper(trim(($person->document ?? '') . ' · ' . ($person->names ?? '') . ' ' . ($person->last_names ?? ''))) : '-';

    // Fotos reducidas en el servidor sin recortarlas (conservan su proporción), para que el PDF no cargue la imagen original
    $photo_crop = function ($file) {
        $source = $file ? @imagecreatefromstring((string) @file_get_contents($file->pdf_path)) : false;
        if (!$source) {
            return null;
        }
        $source_width = imagesx($source);
        $source_height = imagesy($source);
        $scale = min(1, 700 / $source_width, 900 / $source_height);
        $target_width = (int) round($source_width * $scale);
        $target_height = (int) round($source_height * $scale);
        $target = imagecreatetruecolor($target_width, $target_height);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height);
        ob_start();
        imagejpeg($target, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return 'data:image/jpeg;base64,' . base64_encode($jpeg);
    };
@endphp
    {{-- Página 1: encabezado, referencia, información y fotos en una sola hoja --}}
    <div class="page-one flow">
    {{-- Encabezado: solo en la primera hoja --}}
    <table class="kh">
        <tr>
            <td style="width: 27%;"><img src="{{ $logo }}" style="width: 105px;"></td>
            <td class="kh-title">FICHA TÉCNICA</td>
            <td style="width: 17%;"><img src="{{ $qr }}" style="width: 58px; height: 58px;"></td>
        </tr>
    </table>

    {{-- Referencia, con marca y colección --}}
    <div class="hero">
        <div class="hero-ref"><span>Referencia</span><b>{{ $reference }}</b></div>
        <div class="hero-side">
            <div><span>Marca</span><b>{{ $sheet->product->trademark->name ?? '-' }}</b></div>
            <div><span>Colección</span><b>{{ $collection }}</b></div>
        </div>
    </div>

    {{-- Origen y derivados: solo si viene de una transformación, tiene transformaciones o (ficha técnica) se reasignó curva --}}
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
        <div class="grid">
            @foreach ($product_info as [$label, $value])
                <div class="kv {{ in_array($label, ['Color', 'Tono lavado']) ? 'kv-span2' : '' }}"><span>{{ $label }}</span><b>{{ $value }}</b></div>
            @endforeach
        </div>
    </div>

    <div class="sec">
        <div class="sec-title">Información de la ficha técnica</div>
        <div class="grid">
            @foreach ($sheet_info as [$label, $value])
                @if ($label === 'Estado')
                    @php
                        [$bg, $color] = $status_style($sheet->status);
                    @endphp
                    <div class="kv"><span>{{ $label }}</span><b class="badge" style="background: {{ $bg }}; color: {{ $color }};">{{ $value }}</b></div>
                @else
                    <div class="kv"><span>{{ $label }}</span><b>{{ $value }}</b></div>
                @endif
            @endforeach
            <div class="kv kv-span2"><span>Patronista</span><b>{{ $pattern_maker }}{{ $pattern_maker_role ? ' · ' . mb_strtoupper($pattern_maker_role) : '' }}</b></div>
            <div class="kv kv-wide"><span>Descripción</span><b>{{ $sheet->description ?? '-' }}</b></div>
            <div class="kv kv-wide"><span>Observación</span><b>{{ $sheet->observation ?? '-' }}</b></div>
        </div>
    </div>

    <div class="sec sec-photos">
        <div class="sec-title">Fotos de la prenda</div>
        <div class="photo-cards">
            @foreach ([$sheet->photo_d, $sheet->photo_t] as $file)
                <div class="photo-card">
                    @if ($src = $photo_crop($file))
                        <img src="{{ $src }}">
                    @else
                        <div class="photo-empty"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Tipos de insumo debajo de las fotos, dentro de la página 1 --}}
        <div class="sec">
            <div class="sec-title">Tipos de insumo</div>
            <div class="tcard">
            <table class="t">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th style="width: 45%;">Insumo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sheet->supplies as $supply)
                        <tr>
                            <td>{{ optional($supply->supply_type->first())->name ?? '-' }}</td>
                            <td>{{ optional($supply->supply_type->first())->description ?? '-' }}</td>
                            <td>{{ ($supply->name ?? '-') . ' - ' . ($supply->description ?? '-') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: #94a3b8;">Sin insumos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- Especificaciones de curva con la estructura de la ficha anterior: tela, nombre y color con las cantidades por tela y
         talla, total producido y, abajo, tela bolsillo, promedio, piezas y la observación --}}
    <div class="sec">
        <div class="sec-title">Especificaciones de curva</div>
        <div class="tcard">
            <table class="t sizes curve">
                <tr>
                    <th colspan="3">Tela</th>
                    <th colspan="{{ $visible_sizes->count() + 1 }}">Tallas</th>
                </tr>
                <tr>
                    <th style="width: 6%;">N.°</th>
                    <th style="width: 28%;">Nombre</th>
                    <th style="width: 20%;">Color</th>
                    @foreach ($visible_sizes as $size)
                        <th>{{ $size->name }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
                @for ($i = 0; $i < $fabric_rows; $i++)
                    @php
                        $row_total = 0;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        @if ($i === 0)
                            <td rowspan="{{ $fabric_rows }}" class="name">{{ $fabric }}</td>
                            <td rowspan="{{ $fabric_rows }}" class="name">{{ $fabric_color }}</td>
                        @endif
                        @foreach ($visible_sizes as $size)
                            @php
                                $quantity = optional(($by_size->get($size->id) ?? collect())->get($i))->quantity ?? 0;
                                $row_total += $quantity;
                            @endphp
                            <td>{{ $quantity }}</td>
                        @endforeach
                        <td class="sum">{{ $row_total }}</td>
                    </tr>
                @endfor
                <tr class="total">
                    <td colspan="3">Total producido</td>
                    @foreach ($visible_sizes as $size)
                        <td>{{ ($by_size->get($size->id) ?? collect())->sum('quantity') }}</td>
                    @endforeach
                    <td>{{ $produced_units }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="lbl mid">¿Lleva tela bolsillo?</td>
                    <td class="strong">{{ $production_order->pocket_fabric ? 'Sí' : 'No' }}</td>
                    <td colspan="{{ $visible_sizes->count() + 1 }}" class="lbl obs">Observación</td>
                </tr>
                <tr>
                    <td colspan="2" class="lbl mid">Promedio tela</td>
                    <td class="strong">{{ number_format($produced_units > 0 ? $real_meters / $produced_units : 0, 3, ',', '.') }}</td>
                    <td colspan="{{ $visible_sizes->count() + 1 }}" rowspan="2" class="obs">{{ $production_order->observation ?? '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="lbl mid">Total piezas</td>
                    <td class="strong">{{ count($production_order->pieces) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="sec">
        <div class="sec-title">Distribución de curva original</div>
        <div class="tcard">
        <table class="t sizes">
            <thead>
                <tr>
                    <th>Destino</th>
                    <th>Referencia</th>
                    @foreach ($visible_sizes as $size)
                        <th>{{ $size->name }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($destinations as $destination)
                    <tr>
                        <td class="name">{{ $destination['name'] }}</td>
                        <td class="name">{{ $destination['detail']?->model?->code ?? '-' }}</td>
                        @foreach ($visible_sizes as $size)
                            <td>{{ $size_quantity($destination['detail'], $size) }}</td>
                        @endforeach
                        <td class="sum">{{ $destination['detail'] ? $destination['detail']->production_order_detail_quantities->sum('quantity') : 0 }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="2">Curva total</td>
                    @foreach ($visible_sizes as $size)
                        <td>{{ $product_details->sum(fn($detail) => $size_quantity($detail, $size)) }}</td>
                    @endforeach
                    <td>{{ $product_details->sum(fn($detail) => $detail->production_order_detail_quantities->sum('quantity')) }}</td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

@php
    $status_style = fn($status) => match ($status) {
        'Aprobado' => ['#ECFDF5', '#047857', 'Aprobado'],
        'Pendiente' => ['#FFFBEB', '#B45309', 'Pendiente'],
        'En revision' => ['#DBEAFE', '#1D4ED8', 'En revisión'],
        default => ['#F3F4F6', '#6B7280', 'No asociado'],
    };
@endphp
    {{-- Procesos: cada uno en una tarjeta con su tipología y estado; debajo sus subprocesos, operaciones y tablas --}}
    @php
        $detail_of = fn($model) => $sheet->technical_sheet_details->first(
            fn($item) => $item->model_type === $model::class && $item->model_id === $model->id,
        );
        $typology_of = function ($detail) {
            $typology = $detail?->typology->first();
            $code = $typology->settings->code ?? null;
            $name = $typology?->name;

            return $code && $name ? $code . ' - ' . $name : ($code ?: $name);
        };
        $tables_of = function ($detail) {
            $dinamic = data_get($detail, 'settings.dinamic');
            $static = data_get($detail, 'settings.static');

            return [
                'dinamic' => !empty($dinamic->body) && !empty($dinamic->values) ? $dinamic : null,
                'static' => !empty($static->body) && !empty((array) ($static->values ?? [])) ? $static : null,
            ];
        };
        $cards = $processes->map(function ($process) use ($detail_of) {
            $children = [];
            foreach ($process->subprocesses_with_trashed as $subprocess) {
                if ($detail = $detail_of($subprocess)) {
                    $children[] = ['level' => 'subprocess', 'model' => $subprocess, 'detail' => $detail];
                    foreach ($subprocess->operations_with_trashed as $operation) {
                        if ($operation_detail = $detail_of($operation)) {
                            $children[] = ['level' => 'operation', 'model' => $operation, 'detail' => $operation_detail];
                        }
                    }
                }
            }

            return ['model' => $process, 'detail' => $detail_of($process), 'children' => $children];
        })->filter(fn($card) => $card['detail'])->values();
    @endphp

    <div class="sec">
        <div class="sec-title">Procesos</div>
        @foreach ($cards as $index => $card)
            {{-- Las tarjetas sin tablas grandes no se parten entre páginas --}}
            <div class="proc {{ collect([$card['detail']])->merge(array_column($card['children'], 'detail'))->contains(fn($detail) => $tables_of($detail)['dinamic']) ? '' : 'proc-keep' }}">
                @foreach (array_merge([['level' => 'process', 'model' => $card['model'], 'detail' => $card['detail']]], $card['children']) as $row)
                    @php
                        [$bg, $color, $label] = $status_style($row['detail']->status ?? null);
                        $typology = $typology_of($row['detail']);
                        $tables = $tables_of($row['detail']);
                        // El título de la tabla no se muestra si se llama igual que su proceso, subproceso u operación
                        $caption = fn($title) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii(trim((string) $title))) === \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii(trim($row['model']->name))) ? null : ($title ?: null);
                    @endphp
                    @if ($row['level'] === 'process')
                        <div class="proc-head">
                            <span class="proc-num">{{ $index + 1 }}</span>
                            <span class="proc-name">{{ $row['model']->name }}</span>
                            <span class="proc-meta"><span class="muted" @if ($typology) style="font-style: normal; color: #334155;" @endif>{{ $typology ?: 'Sin tipología' }}</span><span class="badge" style="background: {{ $bg }}; color: {{ $color }};">{{ $label }}</span></span>
                        </div>
                    @else
                        <div class="sub {{ $row['level'] === 'operation' ? 'op' : '' }}">
                            <span class="tag">{{ $row['level'] === 'operation' ? 'OPERACIÓN' : 'SUBPROCESO' }}</span><b>{{ $row['model']->name }}</b>
                            <span class="proc-meta"><span class="muted" @if ($typology) style="font-style: normal; color: #334155;" @endif>{{ $typology ?: 'Sin tipología' }}</span><span class="badge" style="background: {{ $bg }}; color: {{ $color }};">{{ $label }}</span></span>
                        </div>
                    @endif

                    @if ($tables['dinamic'])
                        @php
                            // Las columnas de textos cortos no se parten, para que las largas (descripción) tengan espacio
                            $short = collect($tables['dinamic']->body)->map(fn($column) => collect($tables['dinamic']->values)->every(fn($row) => mb_strlen((string) data_get($row, $column->field)) <= 16))->all();
                            // La columna de textos más largos (la descripción) se queda con más ancho
                            $widest = collect($tables['dinamic']->body)->map(fn($column) => collect($tables['dinamic']->values)->avg(fn($row) => mb_strlen((string) data_get($row, $column->field))))->sort()->keys()->last();
                        @endphp
                        @if ($title = $caption($tables['dinamic']->header ?? null))
                            <div class="t-caption">{{ $title }}</div>
                        @endif
                        <table class="t">
                            <thead>
                                <tr>
                                    @foreach ($tables['dinamic']->body as $c => $column)
                                        <th @if ($c === $widest) style="width: 38%;" @endif>{{ $column->label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tables['dinamic']->values as $values)
                                    <tr>
                                        @foreach ($tables['dinamic']->body as $c => $column)
                                            <td @if ($short[$c]) style="white-space: nowrap;" @endif>{{ filled($v = data_get($values, $column->field)) ? $v : 'N/A' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if ($tables['static'])
                        @if ($title = $caption($tables['static']->header->label ?? null))
                            <div class="t-caption">{{ $title }}</div>
                        @endif
                        <table class="t static">
                            @foreach ($tables['static']->body as $static_row)
                                <tr>
                                    @foreach ($static_row as $cell)
                                        @if ($cell->cell == 'th')
                                            <td colspan="{{ $cell->colspan ?? 1 }}" rowspan="{{ $cell->rowspan ?? 1 }}" class="label">{{ $cell->label ?? '-' }}</td>
                                        @else
                                            <td colspan="{{ $cell->colspan ?? 1 }}" rowspan="{{ $cell->rowspan ?? 1 }}" style="font-weight: 600;">
                                                {{ filled($v = data_get($tables['static']->values, $cell->field)) ? $v : 'N/A' }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</body>

</html>
@endif
