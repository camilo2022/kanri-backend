<?php

namespace App\Traits;

use Carbon\Carbon;

/**
 * Valor de una columna de reporte leído de un registro por su data_key, con el formato que eligió el usuario
 * (relación nombre / código - nombre, fecha, sí-no, operación). Lo usan DataCollection (reportes por modelo) y
 * ReportController::source_rows (columnas descriptivas de los reportes calculados). Quien lo use debe usar ModelTypes.
 */
trait ReportValues
{
    protected function report_value($report, $item, $configured_field, $context_field = null)
    {
        $data_key = data_get($item, 'data_key');
        $format = data_get($item, 'format');
        $display = data_get($item, 'display');

        if ($display === 'code_name') {

            $relation = data_get($report, $data_key);
            $options = collect(data_get($configured_field, 'display.options', []));
            $option = $options->firstWhere('value', 'code_name');

            if ($relation && $option) {

                $paths = data_get($option, 'paths', []);
                $separator = data_get($option, 'separator', ' - ');

                $values = collect($paths)
                    ->map(function ($path) use ($relation) {
                        return data_get($relation, $path, 'N/A');
                    })
                    ->filter(function ($value) {
                        return filled($value);
                    });

                $value = $values->implode($separator);

            } else {
                $value = null;
            }

        } else {

            if (data_get($context_field, 'type') === 'selectdinamic') {
                $model = data_get($context_field, 'model');
                $id = data_get($report, $data_key);

                if ($model && $id !== null) {
                    $related_model = $model::find($id);

                    if ($related_model) {
                        $model_types = $this->getModelTypes();

                        $model_type = collect($model_types)
                            ->firstWhere('model', $model);

                        $options = data_get($model_type, 'option');

                        if (is_array($options)) {
                            $value = collect($options)
                                ->map(function ($option) use ($related_model) {
                                    return data_get($related_model, $option);
                                })
                                ->filter(fn ($value) => filled($value))
                                ->implode(' ');
                        } else {
                            $value = data_get($related_model, $options);
                        }
                    } else {
                        $value = null;
                    }
                } else {
                    $value = data_get(
                        $report,
                        $data_key,
                        str_contains($data_key, 'production_order.supplier') ? 'BLESS' : null
                    );
                }
            } else {
                $value = data_get(
                    $report,
                    $data_key,
                    str_contains($data_key, 'production_order.supplier') ? 'BLESS' : null
                );
            }

            if (data_get($context_field, 'type') === 'boolean') {
                if ($value === 'true') {
                    $value = 'Sí';
                } else {
                    $value = 'No';
                }
            }
        }

        if (data_get($configured_field, 'display.type') === 'operation') {
            $relation = data_get($configured_field, 'display.value.relation');
            $operation = data_get($configured_field, 'display.value.operation');
            $property = data_get($configured_field, 'display.value.property');

            $details = collect(data_get($report, $relation) ?? []);

            switch (strtoupper($operation)) {
                case 'SUM':
                    $value = $details->sum(function ($detail) use ($property) {
                        return (float) data_get($detail, $property, 0);
                    });
                    break;

                case 'COUNT':
                    $value = $details->count();
                    break;

                case 'AVG':
                    $value = $details->avg(function ($detail) use ($property) {
                        return (float) data_get($detail, $property, 0);
                    });
                    break;

                case 'MIN':
                    $value = $details->min(function ($detail) use ($property) {
                        return (float) data_get($detail, $property, 0);
                    });
                    break;

                case 'MAX':
                    $value = $details->max(function ($detail) use ($property) {
                        return (float) data_get($detail, $property, 0);
                    });
                    break;

                default:
                    $value = null;
                    break;
            }
        }

        if ($format && $value instanceof Carbon) {
            $value = $value->format($format);
        }

        if ($format === 'yes_no' && $value !== null) {
            $value = $value ? 'Sí' : 'No';
        }

        if ($format === 'true_false' && $value !== null) {
            $value = $value ? 'True' : 'False';
        }

        return $value;
    }

    /**
     * Configuración de la columna en settings.fields para un data_key: la exacta o, si se le agregó la propiedad a mostrar
     * (color.name), la del camino más largo que lo contiene.
     */
    protected function report_configured_field($configured_fields, string $data_key)
    {
        return $configured_fields->get($data_key) ?? $configured_fields
            ->filter(function ($field, $key) use ($data_key) {
                return str_starts_with($data_key, $key.'.');
            })
            ->sortByDesc(function ($field, $key) {
                return strlen($key);
            })
            ->first();
    }
}
