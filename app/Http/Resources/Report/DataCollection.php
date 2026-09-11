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
        $styles = [];

        foreach ($this->fields as $item) {

            $data_key = data_get($item, 'data_key');

            if (! $data_key) {
                continue;
            }

            if (preg_match('/^(\d+)_(.+)$/', $data_key, $matches)) {
                $process_name = $matches[2];

                $labels[] = $process_name;

                $process_styles = data_get($this->configured_fields, 'process.display.styles', []);

                $process_styles = json_decode(json_encode($process_styles), true);

                if (! empty($process_styles)) {
                    $styles[$process_name] = $process_styles;
                }

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

            $field_styles = data_get($configured_field, 'display.styles', []);

            $field_styles = json_decode(json_encode($field_styles), true);

            if (! empty($field_styles)) {
                $styles[$label] = $field_styles;
            }

        }

        $data = collect($this->resource)
            ->map(function ($report) use ($configured_fields, &$labels) {

                $row = [];

                foreach ($this->fields as $item) {

                    $data_key = data_get($item, 'data_key');
                    $format = data_get($item, 'format');
                    $display = data_get($item, 'display');

                    if (! $data_key) {
                        continue;
                    }

                    if (preg_match('/^(\d+)_(.+)$/', $data_key, $matches)) {

                        $process_id = (int) $matches[1];
                        $process_name = $matches[2];

                        $detail = collect($report->technical_sheet_details_processes ?? [])
                            ->firstWhere('model_id', $process_id);

                        $row[$process_name] = data_get($detail, 'status', '-');

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
                        $value = data_get($report, $data_key);
                    }

                    logger([$label, $value]);

                    if ($format && $value instanceof Carbon) {
                        $value = $value->format($format);
                    }

                    if ($format === 'yes_no' && $value !== null) {
                        $value = $value ? 'Sí' : 'No';
                    }

                    if ($format === 'true_false' && $value !== null) {
                        $value = $value ? 'True' : 'False';
                    }

                    $row[$label] = $value;
                }

                return $row;
            })
            ->values();

        return [
            'data' => $data,
            'fields' => $labels,
            'styles' => $styles,
        ];

    }
}
