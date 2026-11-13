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
            <td class="meta-cell" style="width: 10%;">GP-FR-01 V.01</td>
            <td class="qr-cell" rowspan="2"><img src="{{ $qr }}" width="90"></td>
        </tr>

        <tr>
            <td class="title-primary" rowspan="2" style="width: 40%;">FICHA DE PROCESOS</td>
            <td class="meta-cell" style="width: 10%;">13/04/2026</td>
        </tr>

        <tr>
            <td class="meta-cell" colspan="2">Impreso: {{ now()->format('d/m/Y h:i A') }}</td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">INFORMACIÓN PRODUCTO</div>

        <table class="info-table">
            <tr>
                <td class="label">Referencia</td>
                <td class="value">
                    {{ collect([$technical_sheet->product->code ?? null])->merge($technical_sheet->products->pluck('code'))->filter()->implode(' · ') ?: '-' }}
                </td>

                <td class="label">Marca</td>
                <td class="value">{{ $technical_sheet->product->trademark->name ?? '-' }}</td>

                <td class="label">Colección</td>
                <td class="value">
                    {{ ($technical_sheet->collection->settings->code ?? '-') . ' - ' . ($technical_sheet->collection->name ?? '-') }}
                </td>
            </tr>

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
                    {{ ($technical_sheet->pattern_maker->person->document ?? '-') . ' - ' . ($technical_sheet->pattern_maker->person->names ?? '-') . ' ' . ($technical_sheet->pattern_maker->person->last_names ?? '-') . ' - ' . ($technical_sheet->pattern_maker->position->name ?? 'No Aplica') }}
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

    @include('pdf.TechnicalSheet.processes')

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">LÍNEA DE TRABAJO</div>
        <table style="width: 100%; border-collapse: collapse; margin-top: 8px;">
            @foreach ($line as $index => $step)
                @php
                    $active = $step['applies'] && ($step['any_workstation'] || count($step['steps']));
                    $color = $active ? '#24247F' : '#CBD5E1';
                @endphp
                <tr>
                    <td style="width: 34px; vertical-align: top; text-align: center; padding: 0;">
                        <div style="width: 22px; height: 18px; padding-top: 4px; margin: 0 auto; border-radius: 11px; background: {{ $color }}; color: #FFFFFF; font-size: 10px; font-weight: bold; line-height: 1;">{{ $index + 1 }}</div>
                        @if (! $loop->last)
                            <div style="width: 2px; height: 30px; margin: 0 auto; background: #CBD5E1;"></div>
                        @endif
                    </td>
                    <td style="vertical-align: top; padding: 2px 0 10px 6px;">
                        <span style="font-size: 10px; font-weight: bold; color: {{ $active ? '#1E293B' : '#94A3B8' }};">{{ $step['name'] }}</span>
                        @if ($active && ! $step['any_workstation'])
                            <span style="margin-left: 6px; padding: 1px 6px; border-radius: 6px; background: {{ $step['sequence'] ? '#DBEAFE' : '#ECFDF5' }}; color: {{ $step['sequence'] ? '#1D4ED8' : '#047857' }}; font-size: 7px; font-weight: bold;">
                                {{ $step['sequence'] ? 'EN SECUENCIA' : 'EN PARALELO' }}
                            </span>
                        @endif
                        <div style="margin-top: 4px;">
                            @if (! $step['applies'])
                                <span style="font-size: 8px; color: #94A3B8;">No pasa por este proceso</span>
                            @elseif ($step['any_workstation'])
                                <span style="font-size: 8px; color: #475569;">Lo trabaja cualquier puesto de {{ $step['name'] }}</span>
                            @elseif (! count($step['steps']))
                                <span style="font-size: 8px; color: #94A3B8;">No tiene puestos asignados en la ficha</span>
                            @else
                                @foreach ($step['steps'] as $position => $item)
                                    @if ($position > 0)
                                        <span style="font-size: 10px; color: #94A3B8; padding: 0 2px; vertical-align: middle;">{{ $step['sequence'] ? '»' : '' }}</span>
                                    @endif
                                    <span style="display: inline-block; vertical-align: middle; margin: 2px 0; padding: 3px 8px; border: 1px solid #CBD5E1; border-radius: 8px; background: #F8FAFC; font-size: 8px; color: #1E293B;">
                                        @if ($item['group'])
                                            <span style="color: #64748B;">{{ $item['group'] }} ›</span>
                                        @endif
                                        <b>{{ $item['name'] }}</b>
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</body>

</html>
