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
    // Ficha de procesos (workflow sheet), impresa con Chrome (ChromePdfService): página 1 con la referencia, el origen y
    // los derivados, la información y las fotos; después la línea de trabajo dibujada y los procesos. El encabezado va
    // solo en la página 1. Todo en este archivo, incluido el pie (rama footer).
    $sheet = $technical_sheet;
    $reference = collect([$sheet->product->code ?? null])->merge($sheet->products->pluck('code'))->filter()->implode(' · ') ?: '-';
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
        ['Fecha', $sheet->date ? \Illuminate\Support\Carbon::parse($sheet->date)->format('d/m/Y') : '-'],
        ['Órdenes de producción', $sheet->production_orders_count],
    ];
    $pattern_maker_role = collect([
        $sheet->pattern_maker->position->name ?? null,
        optional($sheet->pattern_maker->position?->area->first())->name,
    ])->filter()->implode(' · ');
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
    <div class="page-one">
    {{-- Encabezado: solo en la primera hoja --}}
    <table class="kh">
        <tr>
            <td style="width: 27%;"><img src="{{ $logo }}" style="width: 105px;"></td>
            <td class="kh-title">FICHA DE PROCESOS</td>
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
            <div class="kv kv-wide"><span>Patronista</span><b>{{ $pattern_maker }}{{ $pattern_maker_role ? ' · ' . mb_strtoupper($pattern_maker_role) : '' }}</b></div>
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

    </div>

    {{-- Línea de trabajo: cada proceso es un recuadro con sus puestos en orden; las flechas siguen el recorrido --}}
    @php
        $W = 630;
        $PAD = 12;
        $HEAD = 28;
        $GAP_X = 20;
        $PER_ROW = 4;
        $NODE_W = ($W - 2 * $PAD - ($PER_ROW - 1) * $GAP_X) / $PER_ROW;
        $SMALL_W = 194;
        $SMALL_GAP = 24;
        // Alto disponible para el dibujo en su hoja (el resto es el título y el borde)
        $GRAPH_TARGET = 910;

        // Alto que necesita el texto de un recuadro: título y, debajo, las operaciones como etiquetas que bajan de línea
        $text_height = function ($node, $width) {
            $title_lines = (int) ceil(max(1, mb_strlen($node['title'])) / ($width / 7.5));
            $chip_lines = 0;
            $used = $width;
            foreach ($node['ops'] as $op) {
                $chip = mb_strlen($op) * 4.4 + 12;
                if ($used + $chip > $width) {
                    $chip_lines++;
                    $used = 0;
                }
                $used += $chip + 3;
            }

            return 14 + $title_lines * 10.5 + ($chip_lines ? 4 + $chip_lines * 13 : 0);
        };

        $processes_line = collect($line)->filter(fn($step) => $step['applies'])->values()->map(function ($step, $index) {
            $groups = [];
            $position = 0;
            foreach ($step['steps'] as $item) {
                $position++;
                $last = end($groups);
                if ($item['group'] && $last && $last['group'] === $item['group']) {
                    $groups[key($groups)]['ops'][] = $item['name'];
                    $groups[key($groups)]['to'] = $position;
                } else {
                    $groups[] = ['group' => $item['group'], 'ops' => [$item['name']], 'from' => $position, 'to' => $position];
                }
            }
            $nodes = collect($groups)->map(fn($group) => [
                'title' => $group['group'] ?? $group['ops'][0],
                'step' => $step['sequence'] ? ($group['from'] === $group['to'] ? $group['from'] : $group['from'] . '–' . $group['to']) : null,
                'ops' => $group['group'] ? $group['ops'] : [],
                'muted' => false,
            ])->all();
            if ($step['any_workstation']) {
                $nodes = [['title' => 'Cualquier puesto de ' . $step['name'], 'step' => null, 'ops' => [], 'muted' => false]];
            } elseif (!$nodes) {
                $nodes = [['title' => 'Sin puestos en la ficha', 'step' => null, 'ops' => [], 'muted' => true]];
            }
            $flow = match (true) {
                !count($step['steps']) && !$step['any_workstation'] => 'Completo',
                $step['any_workstation'] => 'Cualquier puesto',
                $step['sequence'] => 'En secuencia',
                default => 'En paralelo',
            };

            return ['number' => $index + 1, 'name' => $step['name'], 'flow' => $flow, 'nodes' => $nodes];
        });

        // Filas: los procesos de un solo puesto van de a 3 en una fila; los de varios, a lo ancho
        $layout_rows = [];
        foreach ($processes_line as $process_line) {
            $small = count($process_line['nodes']) <= 1;
            $last = end($layout_rows);
            if ($small && $last && $last['small'] && count($last['items']) < 3) {
                $layout_rows[key($layout_rows)]['items'][] = $process_line;
            } else {
                $layout_rows[] = ['small' => $small, 'items' => [$process_line]];
            }
        }

        // Flecha en codo con esquinas redondeadas: baja, cruza hasta la columna del destino y vuelve a bajar
        $elbow = function ($x1, $y1, $x2, $y2, $mid) {
            if (abs($x2 - $x1) < 1) {
                return "M$x1 $y1 L$x1 $y2";
            }
            $side = $x2 > $x1 ? 1 : -1;
            $radius = min(6, abs($x2 - $x1) / 2);

            return "M$x1 $y1 L$x1 " . ($mid - $radius) . " Q$x1 $mid " . ($x1 + $side * $radius) . " $mid L" . ($x2 - $side * $radius) . " $mid Q$x2 $mid $x2 " . ($mid + $radius) . " L$x2 $y2";
        };

        // Distribución con un factor de estiramiento: el alto mínimo de los recuadros y los espacios crecen con $k
        $layout = function ($k) use ($layout_rows, $W, $PAD, $HEAD, $GAP_X, $PER_ROW, $NODE_W, $SMALL_W, $SMALL_GAP, $text_height, $elbow) {
            $NODE_H = 44 * $k;
            $GAP_ROW = 24 * $k;
            $GAP_GROUP = 24 * $k;
            $node_height = fn($node, $width) => max($NODE_H, $text_height($node, $width - 20));
            $groups_out = [];
            $nodes_out = [];
            $paths = [];
            $boxes = [];
            $y = 4;
            foreach ($layout_rows as $row) {
                if ($row['small']) {
                    $count = count($row['items']);
                    $nh = collect($row['items'])->map(fn($item) => $node_height($item['nodes'][0], $SMALL_W - 2 * $PAD))->max();
                    $height = $HEAD + $nh + $PAD;
                    $x0 = ($W - ($count * $SMALL_W + ($count - 1) * $SMALL_GAP)) / 2;
                    foreach ($row['items'] as $i => $item) {
                        $gx = $x0 + $i * ($SMALL_W + $SMALL_GAP);
                        $groups_out[] = [$gx, $y, $SMALL_W, $height, $item];
                        $nodes_out[] = [$gx + $PAD, $y + $HEAD, $SMALL_W - 2 * $PAD, $nh, $item['nodes'][0]];
                        $boxes[] = ['x' => $gx, 'y' => $y, 'w' => $SMALL_W, 'h' => $height, 'row' => $y];
                    }
                    $y += $height + $GAP_GROUP;
                } else {
                    $item = $row['items'][0];
                    // Los puestos con 3 o más operaciones ocupan dos espacios, para que sus etiquetas no alarguen la fila
                    $slots = [];
                    [$r, $slot] = [0, 0];
                    foreach ($item['nodes'] as $n => $node) {
                        $span = count($node['ops']) >= 3 ? 2 : 1;
                        if ($slot + $span > $PER_ROW) {
                            [$r, $slot] = [$r + 1, 0];
                        }
                        $slots[$n] = ['row' => $r, 'slot' => $slot, 'width' => $span * $NODE_W + ($span - 1) * $GAP_X];
                        $slot += $span;
                    }
                    // Cada fila de recuadros toma el alto del recuadro con más texto
                    $row_heights = collect($item['nodes'])->map(fn($node, $n) => ['row' => $slots[$n]['row'], 'height' => $node_height($node, $slots[$n]['width'])])
                        ->groupBy('row')->map(fn($row) => $row->max('height'))->values();
                    $row_tops = [];
                    $top = $y + $HEAD;
                    foreach ($row_heights as $row_height) {
                        $row_tops[] = $top;
                        $top += $row_height + $GAP_ROW;
                    }
                    $height = $top - $GAP_ROW + $PAD - $y;
                    $groups_out[] = [0, $y, $W, $height, $item];
                    $previous = null;
                    foreach ($item['nodes'] as $n => $node) {
                        $r = $slots[$n]['row'];
                        $nw = $slots[$n]['width'];
                        $nh = $row_heights[$r];
                        $nx = $PAD + $slots[$n]['slot'] * ($NODE_W + $GAP_X);
                        $ny = $row_tops[$r];
                        $nodes_out[] = [$nx, $ny, $nw, $nh, $node];
                        if ($previous) {
                            [$px, $py, $pw, $ph, $pr] = $previous;
                            if ($pr !== $r) {
                                // Fin de fila → inicio de la siguiente
                                $paths[] = $elbow($px + $pw / 2, $py + $ph, $nx + $nw / 2, $ny - 2, $py + $ph + $GAP_ROW / 2);
                            } else {
                                $y1 = $py + $ph / 2;
                                $y2 = $ny + $nh / 2;
                                $x1 = $px + $pw;
                                $paths[] = "M$x1 $y1 C" . ($x1 + 10) . " $y1 " . ($nx - 10) . " $y2 " . ($nx - 2) . " $y2";
                            }
                        }
                        $previous = [$nx, $ny, $nw, $nh, $r];
                    }
                    $boxes[] = ['x' => 0, 'y' => $y, 'w' => $W, 'h' => $height, 'row' => $y];
                    $y += $height + $GAP_GROUP;
                }
            }

            // Flechas entre procesos: a la derecha si están en la misma fila; si no, en codo hacia el siguiente
            foreach ($boxes as $i => $box) {
                if ($i === 0) {
                    continue;
                }
                $previous = $boxes[$i - 1];
                if ($previous['row'] === $box['row']) {
                    $cy = $box['y'] + $box['h'] / 2;
                    $paths[] = 'M' . ($previous['x'] + $previous['w']) . " $cy L" . ($box['x'] - 2) . " $cy";
                } else {
                    $y1 = $previous['y'] + $previous['h'];
                    $paths[] = $elbow($previous['x'] + $previous['w'] / 2, $y1, $box['x'] + $box['w'] / 2, $box['y'] - 2, ($y1 + $box['y']) / 2);
                }
            }

            return [$groups_out, $nodes_out, $paths, $y - $GAP_GROUP + 4];
        };

        // El dibujo se estira hasta llenar su hoja; si no cabe, queda en su tamaño normal
        $k = 1;
        while ($k < 2.5 && $layout($k + 0.05)[3] <= $GRAPH_TARGET) {
            $k += 0.05;
        }
        [$groups_out, $nodes_out, $paths, $graph_height] = $layout($k);
    @endphp

    <div class="sec page-break page-graph" style="margin-top: 0;">
        <div class="sec-title">Línea de trabajo</div>
        <div class="graph-wrap">
            <div class="graph" style="width: {{ $W }}px; height: {{ $graph_height }}px;">
                @foreach ($groups_out as [$gx, $gy, $gw, $gh, $item])
                    <div class="group" style="left: {{ $gx }}px; top: {{ $gy }}px; width: {{ $gw }}px; height: {{ $gh }}px;">
                        <div class="g-head"><span class="g-num">{{ $item['number'] }}</span>{{ $item['name'] }}<span class="g-flow">{{ $item['flow'] }}</span></div>
                    </div>
                @endforeach
                <svg class="edges" width="{{ $W }}" height="{{ $graph_height }}" viewBox="0 0 {{ $W }} {{ $graph_height }}">
                    <defs>
                        <marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
                            <path d="M1 1 L9 5 L1 9" fill="none" stroke="#1857b6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </marker>
                    </defs>
                    @foreach ($paths as $path)
                        <path d="{{ $path }}" class="edge" marker-end="url(#arrow)" />
                    @endforeach
                </svg>
                @foreach ($nodes_out as [$nx, $ny, $nw, $nh, $node])
                    <div class="node {{ $node['muted'] ? 'node-muted' : '' }}" style="left: {{ $nx }}px; top: {{ $ny }}px; width: {{ $nw }}px; height: {{ $nh }}px;">
                        <div class="n-title">
                            @if ($node['step'])
                                <span class="n-step">{{ $node['step'] }}</span>
                            @endif{{ $node['title'] }}
                        </div>
                        @if ($node['ops'])
                            <div class="n-ops">
                                @foreach ($node['ops'] as $op)
                                    <span class="n-op">{{ $op }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
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

    <div class="sec page-break" style="margin-top: 0;">
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
