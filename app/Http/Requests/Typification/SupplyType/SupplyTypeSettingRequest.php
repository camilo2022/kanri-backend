<?php

namespace App\Http\Requests\Typification\SupplyType;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplyTypeSettingRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'settings' => ['required', 'array'],
            'settings.form' => ['required'],
            'settings.form.*.field' => ['required', 'string', 'regex:/^[a-z]+(_[a-z]+)*$/'],
            'settings.form.*.label' => ['required', 'string', 'uppercase'],
            'settings.form.*.type' => ['required', 'string'],
            'settings.form.*.rules' => ['array'],
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Es requerido.',
            'required_with' => 'Es requerido.',
            'string' => 'Debe ser una cadena de caracteres.',
            'distinct'  => 'Ya ha sido tomado en otra validación.',
            'unique' => 'Ya ha sido tomado.',
            'numeric' => 'Debe ser un valor numérico.',
            'array' => 'Debe ser una lista de datos.',
            'max' => 'No debe exceder los :max caracteres.',
            'min' => 'Debe tener minimo :min elementos.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'lowercase' => 'El campo debe estar en mayúsculas.',
            'regex' => 'Formato inválido. Solo letras minúsculas y guiones bajos (_).',
        ];
    }

    public function attributes()
    {
        $attributes = [];

        $schema = $this->input('settings.configuration', []);

        if (!empty($schema)) {

            foreach ($schema['configuration']['body'] as $i => $field) {

                $n = $i + 1;

                $base = "settings.configuration.body.$i";

                $attributes["$base.id"] = "Id (dinamic n° $n)";
                $attributes["$base.field"] = "Nombre del campo (dinamic n° $n)";
                $attributes["$base.label"] = "Nombre visible del campo (dinamic n° $n)";
                $attributes["$base.type"] = "Tipo de campo (dinamic n° $n)";
                $attributes["$base.rules"] = "Reglas del campo (dinamic n° $n)";

                if (!empty($field['rules'])) {

                    foreach ($field['rules'] as $rIndex => $rule) {

                        $attributes["$base.rules.$rIndex"] =
                            "Regla #" . ($rIndex + 1) . " (dinamic n° $n)";
                    }
                }
            }
        }

        return [
            'settings' => 'Configuración',
            'settings.schema' => 'Esquema',
            'settings.schema.dinamic' => 'Estructura dinámica',
            'settings.schema.static' => 'Estructura estática',
            ...$attributes,
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $schema = $this->input('settings.form', []);

            $seenFields = [];
            $seenLabels = [];

            foreach ($schema as $t => $s) {

                $basePath = "settings.form.$t";

                if (isset($s['field'])) {
                    $field = (string) $s['field'];
                    if (isset($seenFields[$field])) {
                        $first = $seenFields[$field] + 1;
                        $validator->errors()->add(
                            "$basePath.field",
                            "El nombre del campo ya ha sido tomado."
                        );
                    } else {
                        $seenFields[$field] = $t;
                    }
                }

                if (isset($s['label'])) {
                    $label = (string) $s['label'];
                    if (isset($seenLabels[$label])) {
                        $first = $seenLabels[$label] + 1;
                        $validator->errors()->add(
                            "$basePath.label",
                            "El nombre visible del campo ya ha sido tomado."
                        );
                    } else {
                        $seenLabels[$label] = $t;
                    }
                }

                $rules = $s['rules'] ?? [];
                $type = $s['type'] ?? null;

                if (in_array($type, ['text', 'textarea', 'number'])) {

                    $array = [];

                    foreach ($rules as $rule) {
                        if (str_starts_with($rule, 'required')) {
                            $array['required'] = true;
                        }

                        if (str_starts_with($rule, 'min:')) {
                            $array['min'] = str_replace('min:', '', $rule);
                        }

                        if (str_starts_with($rule, 'max:')) {
                            $array['max'] = str_replace('max:', '', $rule);
                        }

                        if (str_starts_with($rule, 'regex:')) {
                            $array['regex'] = str_replace('regex:', '', $rule);
                        }
                    }

                    if (array_key_exists('min', $array)) {
                        if (!is_numeric($array['min'])) {
                            $validator->errors()->add(
                                "$basePath.rules.min",
                                "Debe de ser un valor numerico."
                            );
                        }

                        if (is_numeric($array['min']) && $array['min'] <= 0) {
                            $validator->errors()->add(
                                "$basePath.rules.min",
                                "El valor no puede ser negativo o igual a 0"
                            );
                        }
                    }

                    if (array_key_exists('max', $array)) {
                        if (!is_numeric($array['max'])) {
                            $validator->errors()->add(
                                "$basePath.rules.max",
                                "Debe de ser un valor numerico."
                            );
                        }

                        if (is_numeric($array['max']) && $array['max'] < 0) {
                            $validator->errors()->add(
                                "$basePath.rules.max",
                                "El valor no puede ser negativo."
                            );
                        }
                    }

                    if (array_key_exists('min', $array) && array_key_exists('max', $array)) {
                        if (is_numeric($array['max']) && is_numeric($array['min'])) {
                            if ($array['min'] >= $array['max']) {
                                $validator->errors()->add(
                                    "$basePath.rules.min",
                                    "El mínimo no puede ser mayor o igual al maximo."
                                );
                            }
                        }
                    }

                    if (array_key_exists('regex', $array)) {
                        if (!is_string($array['regex']) || @preg_match($array['regex'], '') === false) {
                            $validator->errors()->add(
                                "$basePath.rules.regex",
                                'Debe ser una expresión regular válida.'
                            );
                        }
                    }
                }

                if (in_array($type, ['date', 'datetime'])) {

                    $array = [];

                    foreach ($rules as $rule) {
                        if (str_starts_with($rule, 'required')) {
                            $array['required'] = true;
                        }

                        if (str_starts_with($rule, 'after:')) {
                            $array['after'] = str_replace('after:', '', $rule);
                        }

                        if (str_starts_with($rule, 'before:')) {
                            $array['before'] = str_replace('before:', '', $rule);
                        }
                    }

                    if (array_key_exists('after', $array)) {
                        if ($type === 'date') {
                            $fecha = Carbon::createFromFormat('Y-m-d', $array['after']);

                            if (!empty($array['after'])) {
                                if (!$fecha || $fecha->format('Y-m-d') !== $array['after']) {
                                    $validator->errors()->add(
                                        "$basePath.rules.after",
                                        "Debe de ser una fecha válida."
                                    );
                                }
                            }
                        }

                        if ($type === 'datetime') {
                            $fecha = Carbon::createFromFormat('Y-m-d H:i', $array['after']);

                            if (!empty($array['after'])) {
                                if (!$fecha || $fecha->format('Y-m-d H:i') !== $array['after']) {
                                    $validator->errors()->add(
                                        "$basePath.rules.after",
                                        "Debe de ser una fecha válida."
                                    );
                                }
                            }
                        }
                    }

                    if (array_key_exists('before', $array)) {
                        if ($type === 'date') {
                            $fecha = Carbon::createFromFormat('Y-m-d', $array['before']);

                            if (!empty($array['before'])) {
                                if (!$fecha || $fecha->format('Y-m-d') !== $array['before']) {
                                    $validator->errors()->add(
                                        "$basePath.rules.before",
                                        "Debe de ser una fecha válida."
                                    );
                                }
                            }
                        }

                        if ($type === 'datetime') {
                            $fecha = Carbon::createFromFormat('Y-m-d H:i', $array['before']);

                            if (!empty($array['before'])) {
                                if (!$fecha || $fecha->format('Y-m-d H:i') !== $array['before']) {
                                    $validator->errors()->add(
                                        "$basePath.rules.before",
                                        "Debe de ser una fecha válida."
                                    );
                                }
                            }
                        }
                    }

                    if (array_key_exists('after', $array) && array_key_exists('before', $array)) {
                        if (!empty($array['before']) && !empty($array['after'])) {
                            if ($type === 'date') {
                                if (Carbon::createFromFormat('Y-m-d', $array['after'])->gte(Carbon::createFromFormat('Y-m-d', $array['before']))) {
                                    $validator->errors()->add(
                                        "$basePath.rules.after",
                                        "La fecha de inicio no puede ser mayor o igual que la fecha de fin."
                                    );
                                }
                            }
                            if ($type === 'datetime') {
                                if (Carbon::createFromFormat('Y-m-d H:i', $array['after'])->gte(Carbon::createFromFormat('Y-m-d H:i', $array['before']))) {
                                    $validator->errors()->add(
                                        "$basePath.rules.after",
                                        "La fecha de inicio no puede ser mayor o igual que la fecha de fin."
                                    );
                                }
                            }
                        }
                    }
                }

                if (in_array($type, ['select'])) {

                    $array = [];

                    foreach ($rules as $rule) {
                        if (str_starts_with($rule, 'in')) {
                            $array['in'] = true;
                        }
                    }

                    if (!array_key_exists('in', $array)) {

                        $validator->errors()->add(
                            "$basePath.rules.options",
                            'Valor requerido.'
                        );
                    }
                }

                if (in_array($type, ['selectdinamic'])) {

                    $array = [];

                    foreach ($rules as $rule) {
                        if (str_starts_with($rule, 'exists')) {
                            $array['exists'] = true;
                        }
                    }

                    if (!array_key_exists('exists', $array)) {

                        $validator->errors()->add(
                            "$basePath.rules.model",
                            'Valor requerido.'
                        );
                    }
                }
            }
        });
    }
}
