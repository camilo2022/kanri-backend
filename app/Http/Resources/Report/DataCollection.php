<?php

namespace App\Http\Resources\Report;

use App\Traits\ModelTypes;
use App\Traits\ReportValues;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DataCollection extends JsonResource
{
    use ModelTypes, ReportValues;

    protected array $fields;

    protected $configured_fields;

    protected $context;

    protected $model_type;

    protected $model_id;

    public function __construct($resource, array $fields = [], $configured_fields = [], $context = null, $model_type = null, $model_id = null)
    {
        parent::__construct($resource);
        $this->fields = $fields;
        $this->configured_fields = $configured_fields;
        $this->context = $context;
        $this->model_type = $model_type;
        $this->model_id = $model_id;
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

        $context_model = null;

        if ($this->model_type && $this->model_id) {
            $context_model = $this->model_type::find($this->model_id);
        }

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

            $label = data_get($configured_field, 'label', data_get($item, 'label', $data_key));

            $labels[] = $label;

        }

        $values_path = data_get($this->context, 'source.values');

        $multiple = data_get($this->context, 'source.multiple', false);

        $reports = collect($this->resource)
            ->flatMap(function ($report) use ($values_path, $multiple) {

                if (! $values_path) {
                    return [$report];
                }

                $values = data_get($report, $values_path);

                if ($multiple) {
                    return collect($values ?? [])
                        ->map(function ($value) use ($report) {
                            $row = (array) $value;
                            $row['technical_sheet_details'] = $report;

                            return $row;
                        });
                }

                $row = (array) $values;

                if ($row === []) {
                    return [];
                }

                $row['technical_sheet_details'] = $report;

                return [$row];
            });

        $context_fields = collect(
            data_get($context_model, data_get($this->context, 'source.fields'), [])
        );

        if (! $multiple) {
            $context_fields = $context_fields->flatMap(function ($fields) {
                return collect($fields);
            });
        }

        $data = $reports
            ->map(function ($report) use ($configured_fields, &$labels, $context_fields) {

                $row = [];

                foreach ($this->fields as $item) {

                    $data_key = data_get($item, 'data_key');
                    $format = data_get($item, 'format');
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

                    $configured_field = $this->report_configured_field($configured_fields, $data_key);

                    $label = data_get($configured_field, 'label', data_get($item, 'label', $data_key));

                    $value = $this->report_value($report, $item, $configured_field, $context_fields->firstWhere('field', $data_key));

                    $row[$label]['value'] = $value;

                    $style = null;

                    $style_relation = data_get($configured_field, 'relations.style');
                    $style_relation_path = data_get($style_relation, 'relation');
                    $style_relation_property = data_get($style_relation, 'property');

                    if ($style_relation_path) {
                        $style_detail = data_get(data_get($report, $style_relation_path), data_get($style_relation, 'property'));

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
