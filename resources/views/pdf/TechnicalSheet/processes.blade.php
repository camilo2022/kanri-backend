{{-- Procesos de la ficha técnica con sus tablas, subprocesos y operaciones. Lo usan la ficha técnica y la ficha de procesos. --}}

<div class="section">
    <div class="section-title">PROCESOS</div>
</div>

@foreach ($processes as $process)
    <div class="section">
        @php
            $detail_process = $technical_sheet->technical_sheet_details->first(
                fn($item) => $item->model_type === $process::class && $item->model_id === $process->id,
            );

            $status = $detail_process->status ?? null;
            $typology = $detail_process?->typology->first();

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
                    <td class="label-primary">
                        @php
                            $code = $typology->settings->code ?? null;
                            $name = $typology?->name ?? null;
                        @endphp

                        @if ($code && $name)
                            {{ $code }} - {{ $name }}
                        @elseif ($code)
                            {{ $code }}
                        @elseif ($name)
                            {{ $name }}
                        @else
                            -
                        @endif
                    </td>
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
                fn($item) => $item->model_type === $subprocess::class && $item->model_id === $subprocess->id,
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
                    fn($item) => $item->model_type === $subprocess::class && $item->model_id === $subprocess->id,
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
                    fn($item) => $item->model_type === $operation::class && $item->model_id === $operation->id,
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
                        fn($item) => $item->model_type === $operation::class && $item->model_id === $operation->id,
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
