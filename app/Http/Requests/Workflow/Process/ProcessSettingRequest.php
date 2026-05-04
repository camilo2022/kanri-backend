<?php

namespace App\Http\Requests\Workflow\Process;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessSettingRequest extends FormRequest
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
            'settings.structure' => ['required'],
            'settings.structure.entity' => ['required', 'string', 'uppercase', 'distinct'],
            'settings.structure.schema.*' => ['required', 'array'],
            'settings.structure.schema.*.field' => ['required', 'string', 'regex:/^[a-z]+(_[a-z]+)*$/'],
            'settings.structure.schema.*.label' => ['required', 'string', 'uppercase'],
            'settings.structure.schema.*.type' => ['required', 'string', 'max:255'],
            'settings.structure.schema.*.rules' => ['required', 'array'],
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Es requerido.',
            'string' => 'Debe ser una cadena de caracteres.',
            'distinct'  => 'Ya ha sido tomado en otra validación.',
            'unique' => 'Ya ha sido tomado.',
            'numeric' => 'Debe ser un valor numérico.',
            'array' => 'Debe ser una lista de datos.',
            'max' => 'No debe exceder los :max caracteres.',
            'min' => 'Debe tener minimo :min elementos.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'lowercase' => 'El campo debe estar en mayúsculas.',
            'settings.structure.schema.*.field.regex' => 'Formato inválido. Solo letras minúsculas y guiones bajos (_).',
        ];
    }

    public function attributes()
    {
        $attributes = [];

        foreach ($this->input('settings.structure.schema', []) as $i => $v) {
            $n = $i + 1;
            $base = "settings.structure.schema.$i";

            $attributes["$base.id"] = "Id (validación n° $n)";
            $attributes["$base.field"] = "Nombre del campo (validación n° $n)";
            $attributes["$base.label"] = "Nombre visible del campo (validación n° $n)";
            $attributes["$base.type"] = "Tipo de campo (validación n° $n)";
            $attributes["$base.required"] = "El campo es requerido (validación n° $n)";

            $attributes["$base.rules"] = "Reglas del campo (validación n° $n)";

            if (!empty($v['rules'])) {
                foreach ($v['rules'] as $rIndex => $rule) {
                    $attributes["$base.rules.$rIndex"] = "Regla #" . ($rIndex + 1) . " (validación n° $n)";
                }
            }
        }

        return [
            'settings' => 'Configuración',
            'settings.structure' => 'Estructura de la tabla',
            'settings.structure.entity' => 'Nombre de la tabla',
            ...$attributes,
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $seenFields = [];
            $seenLabels = [];

            foreach ($this->input('settings.structure.schema', []) as $i => $s) {

                if (isset($s['field'])) {
                    $field = (string) $s['field'];

                    if (isset($seenFields[$field])) {
                        $first = $seenFields[$field] + 1;
                        $validator->errors()->add(
                            "settings.structure.schema.$i.field",
                            "El nombre del campo ya ha sido tomado en la validación n° {$first}."
                        );
                    } else {
                        $seenFields[$field] = $i;
                    }
                }

                if (isset($s['label'])) {
                    $label = (string) $s['label'];

                    if (isset($seenLabels[$label])) {
                        $first = $seenLabels[$label] + 1;
                        $validator->errors()->add(
                            "settings.structure.schema.$i.label",
                            "El nombre visible del campo ya ha sido tomado en la validación n° {$first}."
                        );
                    } else {
                        $seenLabels[$label] = $i;
                    }
                }

                $rules = $s['rules'] ?? [];
                $type = $s['type'] ?? null;

                if ($type === 'text' || $type === 'textarea' || $type === 'number') {

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
                                "settings.structure.schema.$i.rules.min",
                                "Debe de ser un valor numerico."
                            );
                        }

                        if (is_numeric($array['min']) && $array['min'] <= 0) {
                            $validator->errors()->add(
                                "settings.structure.schema.$i.rules.min",
                                "El valor no puede ser negativo o igual a 0"
                            );
                        }
                    } else {
                        $validator->errors()->add(
                            "settings.structure.schema.$i.rules.min",
                            'Valor requerido.'
                        );
                    }

                    if (array_key_exists('max', $array)) {
                        if (!is_numeric($array['max'])) {
                            $validator->errors()->add(
                                "settings.structure.schema.$i.rules.max",
                                "Debe de ser un valor numerico."
                            );
                        }

                        if (is_numeric($array['max']) && $array['max'] < 0) {
                            $validator->errors()->add(
                                "settings.structure.schema.$i.rules.max",
                                "El valor no puede ser negativo."
                            );
                        }
                    } else {
                        $validator->errors()->add(
                            "settings.structure.schema.$i.rules.max",
                            'Valor requerido.'
                        );
                    }

                    if (array_key_exists('min', $array) && array_key_exists('max', $array)) {
                        if (is_numeric($array['max']) && is_numeric($array['min'])) {
                            if ($array['min'] >= $array['max']) {
                                $validator->errors()->add(
                                    "settings.structure.schema.$i.rules.min",
                                    "El mínimo no puede ser mayor o igual al maximo."
                                );
                            }
                        }
                    }

                    if (array_key_exists('regex', $array)) {
                        if (!is_string($array['regex']) || @preg_match($array['regex'], '') === false) {
                            $validator->errors()->add(
                                "settings.structure.schema.$i.rules.regex",
                                'Debe ser una expresión regular válida.'
                            );
                        }
                    }
                }



                if ($type === 'date' || $type === 'datetime') {

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
                                        "settings.structure.schema.$i.rules.after",
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
                                        "settings.structure.schema.$i.rules.after",
                                        "Debe de ser una fecha válida."
                                    );
                                }
                            }
                        }
                    } else {
                        $validator->errors()->add(
                            "settings.structure.schema.$i.rules.after",
                            'Valor requerido.'
                        );
                    }

                    if (array_key_exists('before', $array)) {
                        if ($type === 'date') {
                            $fecha = Carbon::createFromFormat('Y-m-d', $array['before']);

                            if (!empty($array['before'])) {
                                if (!$fecha || $fecha->format('Y-m-d') !== $array['before']) {
                                    $validator->errors()->add(
                                        "settings.structure.schema.$i.rules.before",
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
                                        "settings.structure.schema.$i.rules.before",
                                        "Debe de ser una fecha válida."
                                    );
                                }
                            }
                        }
                    } else {
                        $validator->errors()->add(
                            "settings.structure.schema.$i.rules.before",
                            'Valor requerido.'
                        );
                    }

                    if (array_key_exists('after', $array) && array_key_exists('before', $array)) {
                        if (!empty($array['before']) && !empty($array['after'])) {
                            if ($type === 'date') {
                                if (Carbon::createFromFormat('Y-m-d', $array['after'])->gte(Carbon::createFromFormat('Y-m-d', $array['before']))) {
                                    $validator->errors()->add(
                                        "settings.structure.schema.$i.rules.after",
                                        "La fecha de inicio no puede ser mayor o igual que la fecha de fin."
                                    );
                                }
                            }
                            if ($type === 'datetime') {
                                if (Carbon::createFromFormat('Y-m-d H:i', $array['after'])->gte(Carbon::createFromFormat('Y-m-d H:i', $array['before']))) {
                                    $validator->errors()->add(
                                        "settings.structure.schema.$i.rules.after",
                                        "La fecha de inicio no puede ser mayor o igual que la fecha de fin."
                                    );
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}
