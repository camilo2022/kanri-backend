<?php

namespace App\Http\Requests\Report;

use App\Models\Report;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReportExportRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors(),
        ], 422));
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'message' => 'No tienes permisos para generar este reporte.',
            'errors' => [
                'authorization' => 'No tienes permisos para generar este reporte.',
            ],
        ], 403));
    }

    public function authorize(): bool
    {
        $report = Report::find($this->route('id'));

        if (! $report) {
            return false;
        }

        return $this->user()->can('generate', $report);
    }

    public function rules(): array
    {

        return [
            'model' => ['required', 'string'],
            'id' => ['required', 'exists:subitems,id,item_id,'.Report::ITEM_ID],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['array'],
            'filters.*.*' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'fields.required' => 'Debe seleccionar al menos una columna.',
            'fields.array' => 'Las columnas deben enviarse como una lista.',
            'fields.min' => 'Debe seleccionar al menos una columna.',
            'fields.*.required' => 'La columna es obligatoria.',
            'filters.required' => 'Debe seleccionar al menos un filtro.',
            'filters.array' => 'Los filtros deben enviarse como una lista.',
            'filters.*.array' => 'Los valores del filtro deben enviarse como una lista.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del reporte',
            'fields' => 'columnas',
            'fields.*' => 'columna',
            'filters' => 'filtros',
            'filters.*' => 'filtro',
            'filters.*.*' => 'valor del filtro',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            // Filtros que el reporte exige para descargar (settings: required_export) y, según la opción elegida, otro filtro
            // que acote (requires_with: {opción: [filtros]}); así el Excel no pasa de lo que cabe en una petición
            $report_filters = (array) (Report::find($this->route('id'))?->settings?->filters ?? []);
            $chosen = fn ($key) => ! empty(data_get($this->input('filters', []), $key)) && collect(data_get($this->input('filters', []), $key))->filter()->isNotEmpty();

            foreach ($report_filters as $key => $config) {
                if (! data_get($config, 'required_export')) {
                    continue;
                }

                if (! $chosen($key)) {
                    $validator->errors()->add("filters.$key", 'Es obligatorio');

                    continue;
                }

                foreach ((array) data_get($config, 'requires_with', []) as $option => $others) {
                    if (! in_array($option, (array) data_get($this->input('filters', []), $key), true) || collect($others)->contains(fn ($other) => $chosen($other))) {
                        continue;
                    }

                    $option_label = data_get(collect(data_get($config, 'options', []))->firstWhere('value', $option), 'label', $option);
                    $others_labels = collect($others)->map(fn ($other) => data_get($report_filters, $other.'.label', $other))->implode(', ');
                    $validator->errors()->add('filters', "Con «{$option_label}» elige también uno de estos filtros: {$others_labels}.");
                }
            }

            // Solo se aceptan los filtros configurados en el reporte
            $configured = array_keys($report_filters);

            foreach (array_keys((array) $this->input('filters', [])) as $filter_key) {
                if (! in_array($filter_key, $configured, true)) {
                    $validator->errors()->add("filters.$filter_key", 'Este filtro no existe en el reporte.');
                }
            }

            $model_class = $this->input('model');

            if (! str_starts_with($model_class, 'App\\Models\\')) {
                $validator->errors()->add(
                    'model',
                    'El modelo debe pertenecer al espacio de nombres App\\Models.'
                );
            } elseif (! class_exists($model_class)) {
                $validator->errors()->add(
                    'model',
                    "El modelo {$model_class} no existe."
                );
            } elseif (! is_subclass_of($model_class, Model::class)) {
                $validator->errors()->add(
                    'model',
                    "La clase {$model_class} no es un modelo válido."
                );
            }

            $filters = $this->input('filters', []);

            foreach ($filters as $key => $value) {

                if (str_starts_with($key, 'App\\Models\\')) {

                    if (! class_exists($key)) {
                        $validator->errors()->add(
                            "filters.$key",
                            "El modelo {$key} no existe."
                        );

                        continue;
                    }

                    if (! is_subclass_of($key, Model::class)) {
                        $validator->errors()->add(
                            "filters.$key",
                            "La clase {$key} no es un modelo válido."
                        );

                        continue;
                    }

                    if (! is_array($value)) {
                        $validator->errors()->add(
                            "filters.$key",
                            'Los valores del filtro deben ser un array.'
                        );

                        continue;
                    }

                    $model = new $key;

                    if ($key === 'App\\Models\\Process') {

                        $process_ids = collect($value)
                            ->map(function ($item) {
                                return explode('_', $item, 2)[0];
                            })
                            ->filter()
                            ->values()
                            ->all();

                        $existing_ids = $model
                            ->newQuery()
                            ->whereKey($process_ids)
                            ->pluck($model->getKeyName())
                            ->all();

                        $invalid_ids = array_diff(
                            $process_ids,
                            $existing_ids
                        );

                        if (! empty($invalid_ids)) {
                            $validator->errors()->add(
                                "filters.$key",
                                'Los siguientes procesos no existen: '
                                .implode(', ', $invalid_ids)
                            );
                        }

                        continue;
                    }

                    $existing_ids = $model
                        ->newQuery()
                        ->whereKey($value)
                        ->pluck($model->getKeyName())
                        ->all();

                    $invalid_ids = array_diff($value, $existing_ids);

                    if (! empty($invalid_ids)) {
                        $validator->errors()->add(
                            "filters.$key",
                            'Los siguientes valores no existen: '
                            .implode(', ', $invalid_ids)
                        );
                    }
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
