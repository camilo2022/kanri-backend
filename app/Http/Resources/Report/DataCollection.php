<?php

namespace App\Http\Resources\Report;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DataCollection extends JsonResource
{
    protected array $fields;

    protected $configured_fields;

    public function __construct($resource, array $fields = [], $configured_fields = [])
    {
        parent::__construct($resource);
        $this->fields = $fields;
        $this->configured_fields = $configured_fields;
    }

    public function toArray($request)
    {
        $configured_fields = collect($this->configured_fields)
            ->flatMap(function ($group) {
                return collect(data_get($group, 'fields', []));
            })
            ->filter(function ($field) {
                return filled(data_get($field, 'data_key'));
            })
            ->keyBy(function ($field) {
                return data_get($field, 'data_key');
            });

        $labels = [];

        foreach ($this->fields as $item) {

            $data_key = data_get($item, 'data_key');

            if (! $data_key) {
                continue;
            }

            if (preg_match('/^(\d+)_(.+)$/', $data_key, $matches)) {

                $name = $matches[2];

                $labels[] = $name;

                continue;
            }

            $configured_field = $configured_fields->get($data_key);

            if (! $configured_field) {
                $configured_field = $configured_fields
                    ->filter(function ($field, $key) use ($data_key) {
                        return str_starts_with($data_key, $key.'.');
                    })
                    ->sortByDesc(function ($field, $key) {
                        return strlen($key);
                    })
                    ->first();
            }

            $label = data_get($configured_field, 'label', $data_key);

            $labels[] = $label;

        }

        $data = collect($this->resource)
            ->map(function ($report) use ($configured_fields, &$labels) {

                $row = [];

                foreach ($this->fields as $item) {

                    $data_key = data_get($item, 'data_key');
                    $format = data_get($item, 'format');
                    $display = data_get($item, 'display');
                    $key = data_get($item, 'key');

                    if (! $data_key) {
                        continue;
                    }

                    if (preg_match('/^(\d+)_(.+)$/', $data_key, $matches)) {

                        $configured_field_aux = collect($this->configured_fields)->get($key);

                        $item_id = (int) $matches[1];
                        $item_name = $matches[2];

                        $model = data_get($configured_field_aux, 'model');

                        $value = null;

                        $value_relation = data_get($configured_field_aux, 'relations.value');
                        $value_relation_path = data_get($value_relation, 'relation');
                        $value_property_relation = data_get($value_relation, 'property_relation');
                        $value_property = data_get($value_relation, 'property');

                        if ($value_relation_path) {

                            $value_detail = collect(data_get($report, $value_relation_path) ?? []
                            )->first(function ($detail) use ($item_id, $value_property_relation) {
                                return (int) data_get($detail, $value_property_relation) === $item_id;
                            });

                            $value = data_get($value_detail, $value_property);

                        } else {

                            $value_detail = collect($this->resource ?? [])
                                ->first(function ($detail) use ($item_id, $model, $report) {
                                    return data_get($detail, 'production_order_id') === data_get($report, 'production_order_id')
                                        && (int) data_get($detail, 'model_id') === $item_id
                                        && data_get($detail, 'model_type') === $model;
                                });

                            $value = data_get($value_detail, 'settings.date');
                            $value = data_get($value_detail, $value_property);
                        }

                        $style = null;

                        $style_relation = data_get($configured_field_aux, 'relations.style');
                        $style_relation_path = data_get($style_relation, 'relation');

                        if ($style_relation_path) {
                            $style_details = data_get($report, $style_relation_path) ?? [];

                            $style_detail = collect($style_details)->first(function ($detail) use ($item_id, $model, $style_relation) {
                                return (int) data_get($detail, data_get($style_relation, 'property_relation')) === $item_id && data_get($detail, 'model_type') === $model;
                            });

                            $status = data_get($style_detail, data_get($style_relation, 'property'));

                            $styles = json_decode(
                                json_encode(data_get($configured_field_aux, 'display.styles', [])),
                                true
                            );

                            $style = $styles[$status]
                                ?? $styles['default']
                                ?? [];

                        }

                        if ($format && $value !== null && data_get($configured_field_aux, 'display.type') === 'date') {
                            if ($value instanceof Carbon) {
                                $value = $value->format($format);
                            } else {
                                $value = Carbon::parse($value)->format($format);
                            }
                        }

                        $row[$item_name] = [
                            'value' => $value,
                            'style' => $style,
                        ];

                        continue;
                    }

                    $configured_field = $configured_fields->get($data_key);

                    if (! $configured_field) {

                        $configured_field = $configured_fields
                            ->filter(function ($field, $key) use ($data_key) {
                                return str_starts_with($data_key, $key.'.');
                            })
                            ->sortByDesc(function ($field, $key) {
                                return strlen($key);
                            })
                            ->first();
                    }

                    $label = data_get($configured_field, 'label', $data_key);

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
                        logger()->debug('DATA', [
                            'REPORT' => $report,
                            'PRODUCTION_ORDER' => data_get($report, 'production_order'),
                            'DATA_KEY' => $data_key,
                            'RESULT' => data_get($report, $data_key),
                            'PRODUCTION_ORDER_SATELITE' => data_get($report, 'production_order.supplier'),
                        ]);

                        $value = data_get(
                            $report,
                            $data_key,
                            str_contains($data_key, 'production_order.supplier') ? 'BLESS' : null
                        );

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

                    $row[$label]['value'] = $value;

                    $style = null;

                    $style_relation = data_get($configured_field, 'relations.style');
                    $style_relation_path = data_get($style_relation, 'relation');
                    $style_relation_property = data_get($style_relation, 'property');

                    if ($style_relation_path) {
                        $style_detail = data_get(data_get($report, $style_relation_path), data_get($style_relation, 'property'));

                        logger()->debug('DETAILS', [
                            'STYLE DETAIL' => $style_detail,
                        ]);

                        $styles = json_decode(
                            json_encode(data_get($configured_field, 'display.styles', [])),
                            true
                        );

                        $style = $styles[$style_detail]
                            ?? $styles['default']
                            ?? [];
                    } else {
                        if ($style_relation_property) {

                            $style_detail = data_get($report, $style_relation_property);

                            $styles = json_decode(
                                json_encode(data_get($configured_field, 'display.styles', [])),
                                true
                            );

                            $style = $styles[$style_detail]
                                ?? $styles['default']
                                ?? [];

                        }
                    }

                    $row[$label]['style'] = $style;
                }

                return $row;
            })
            ->values();

        return [
            'data' => $data,
            'fields' => $labels,
        ];

    }
}
