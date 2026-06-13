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
            'settings.schema' => ['required'],
            'settings.schema.dinamic' => ['sometimes', 'array'],
            'settings.schema.dinamic.header' => ['required_with:settings.schema.dinamic', 'string', 'uppercase'],
            'settings.schema.dinamic.body' => ['required_with:settings.schema.dinamic', 'array'],
            'settings.schema.dinamic.body.*.field' => ['required', 'string', 'regex:/^[a-z]+(_[a-z]+)*$/'],
            'settings.schema.dinamic.body.*.label' => ['required', 'string', 'uppercase'],
            'settings.schema.dinamic.body.*.type' => ['required', 'string'],
            'settings.schema.dinamic.body.*.rules' => ['array'],
            'settings.schema.static' => ['sometimes', 'array'],
            'settings.schema.static.header' => ['required_with:settings.schema.static'],
            'settings.schema.static.header.label' => ['required_with:settings.schema.static', 'string', 'uppercase'],
            'settings.schema.static.header.colspan' => ['required_with:settings.schema.static', 'numeric'],
            'settings.schema.static.header.rowspan' => ['required_with:settings.schema.static', 'numeric'],
            'settings.schema.static.body' => ['present_with:settings.schema.static', 'array'],
            'settings.schema.static.body.*.*.cell' => ['sometimes', 'required', 'in:th,td'],
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

        $schema = $this->input('settings.schema', []);

        if (!empty($schema['dinamic']['body'])) {

            foreach ($schema['dinamic']['body'] as $i => $field) {

                $n = $i + 1;

                $base = "settings.schema.dinamic.body.$i";

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

        if (!empty($schema['static']['body'])) {

            foreach ($schema['static']['body'] as $rowIndex => $row) {

                foreach ($row as $colIndex => $cell) {

                    // Ignorar null
                    if (is_null($cell)) {
                        continue;
                    }

                    $rowNumber = $rowIndex + 1;
                    $colNumber = $colIndex + 1;

                    $base = "settings.schema.static.body.$rowIndex.$colIndex";

                    $position = "fila $rowNumber columna $colNumber";

                    $attributes["$base.id"] = "Id ($position)";
                    $attributes["$base.cell"] = "Tipo de celda ($position)";
                    $attributes["$base.field"] = "Nombre del campo ($position)";
                    $attributes["$base.label"] = "Nombre visible ($position)";
                    $attributes["$base.type"] = "Tipo de campo ($position)";
                    $attributes["$base.rules"] = "Reglas ($position)";
                    $attributes["$base.colspan"] = "Columnas ocupadas ($position)";
                    $attributes["$base.rowspan"] = "Filas ocupadas ($position)";

                    if (!empty($cell['rules'])) {

                        foreach ($cell['rules'] as $rIndex => $rule) {

                            $attributes["$base.rules.$rIndex"] =
                                "Regla #" . ($rIndex + 1) . " ($position)";
                        }
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

            $schema = $this->input('settings.schema', []);

            foreach ($schema as $type => $config) {

                if ($type === 'dinamic') {

                    $seenFields = [];
                    $seenLabels = [];

                    foreach ($config['body'] ?? [] as $i => $s) {

                        $basePath = "settings.schema.dinamic.body.$i";

                        if (isset($s['field'])) {
                            $field = (string) $s['field'];

                            if (isset($seenFields[$field])) {
                                $first = $seenFields[$field] + 1;
                                $validator->errors()->add(
                                    "$basePath.field",
                                    "El nombre del campo ya ha sido tomado."
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
                                    "$basePath.label",
                                    "El nombre visible del campo ya ha sido tomado."
                                );
                            } else {
                                $seenLabels[$label] = $i;
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
                            } else {
                                $validator->errors()->add(
                                    "$basePath.rules.min",
                                    'Valor requerido.'
                                );
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
                            } else {
                                $validator->errors()->add(
                                    "$basePath.rules.max",
                                    'Valor requerido.'
                                );
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
                    }
                }

                if ($type === 'static') {

                    $seenFields = [];
                    $seenLabels = [];

                    foreach ($config['body'] ?? [] as $rowIndex => $row) {

                        foreach ($row as $colIndex => $s) {

                            if (is_null($s)) {
                                continue;
                            }

                            $basePath = "settings.schema.static.body";

                            $cell = $s['cell'] ?? null;

                            if (!in_array($cell, ['th', 'td'])) {

                                $validator->errors()->add(
                                    "$basePath.cell",
                                    'Debe ser th o td.'
                                );

                                continue;
                            }

                            if ($cell === 'th') {

                                foreach (['colspan', 'rowspan'] as $span) {

                                    if (!isset($s[$span])) {

                                        $validator->errors()->add(
                                            "$basePath.th.$span",
                                            'Valor requerido.'
                                        );

                                        continue;
                                    }

                                    if (!is_numeric($s[$span])) {

                                        $validator->errors()->add(
                                            "$basePath.th.$span",
                                            'Debe ser numérico.'
                                        );

                                        continue;
                                    }

                                    if ((int) $s[$span] <= 0) {

                                        $validator->errors()->add(
                                            "$basePath.th.$span",
                                            'Debe ser mayor a 0.'
                                        );
                                    }
                                }

                                if (empty($s['label'])) {

                                    $validator->errors()->add(
                                        "$basePath.label",
                                        'Valor requerido.'
                                    );
                                } elseif (!is_string($s['label'])) {

                                    $validator->errors()->add(
                                        "$basePath.label",
                                        'Debe ser texto.'
                                    );
                                } else {

                                    $label = trim($s['label']);

                                    if (isset($seenLabels[$label])) {

                                        $validator->errors()->add(
                                            "$basePath.label",
                                            'Ya esta registrado.'
                                        );
                                    } else {

                                        $seenLabels[$label] = true;
                                    }
                                }
                            }

                            if ($cell === 'td') {

                                foreach (['colspan', 'rowspan'] as $span) {

                                    if (!isset($s[$span])) {

                                        $validator->errors()->add(
                                            "$basePath.td.$span",
                                            'Valor requerido.'
                                        );

                                        continue;
                                    }

                                    if (!is_numeric($s[$span])) {

                                        $validator->errors()->add(
                                            "$basePath.td.$span",
                                            'Debe ser numérico.'
                                        );

                                        continue;
                                    }

                                    if ((int) $s[$span] <= 0) {

                                        $validator->errors()->add(
                                            "$basePath.td.$span",
                                            'Debe ser mayor a 0.'
                                        );
                                    }
                                }

                                if (empty($s['field'])) {
                                    $validator->errors()->add(
                                        "$basePath.field",
                                        'Valor requerido.'
                                    );
                                } elseif (
                                    !is_string($s['field']) ||
                                    !preg_match('/^[a-z]+(_[a-z0-9]+)*$/', $s['field'])
                                ) {

                                    $validator->errors()->add(
                                        "$basePath.field",
                                        'Formato inválido.'
                                    );
                                } else {

                                    $field = trim($s['field']);

                                    if (isset($seenFields[$field])) {

                                        $validator->errors()->add(
                                            "$basePath.field",
                                            'El nombre del campo ya existe.'
                                        );
                                    } else {

                                        $seenFields[$field] = true;
                                    }
                                }

                                if (empty($s['type'])) {

                                    $validator->errors()->add(
                                        "$basePath.type",
                                        'Valor requerido.'
                                    );
                                } elseif (!is_string($s['type'])) {

                                    $validator->errors()->add(
                                        "$basePath.type",
                                        'Debe ser texto.'
                                    );
                                }

                                if (!isset($s['rules'])) {
                                    $validator->errors()->add(
                                        "$basePath.rules",
                                        'Valor requerido.'
                                    );
                                } elseif (!is_array($s['rules'])) {

                                    $validator->errors()->add(
                                        "$basePath.rules",
                                        'Debe ser un array.'
                                    );
                                }

                                $rules = $s['rules'] ?? [];
                                $typeField = $s['type'] ?? null;

                                if (in_array($typeField, ['text', 'textarea', 'number'])) {

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
                                    } else {
                                        $validator->errors()->add(
                                            "$basePath.rules.min",
                                            'Valor requerido.'
                                        );
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
                                    } else {
                                        $validator->errors()->add(
                                            "$basePath.rules.max",
                                            'Valor requerido.'
                                        );
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

                                if (in_array($typeField, ['date', 'datetime'])) {

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
                                        if ($typeField === 'date') {
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

                                        if ($typeField === 'datetime') {
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
                                        if ($typeField === 'date') {
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

                                        if ($typeField === 'datetime') {
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
                                            if ($typeField === 'date') {
                                                if (Carbon::createFromFormat('Y-m-d', $array['after'])->gte(Carbon::createFromFormat('Y-m-d', $array['before']))) {
                                                    $validator->errors()->add(
                                                        "$basePath.rules.after",
                                                        "La fecha de inicio no puede ser mayor o igual que la fecha de fin."
                                                    );
                                                }
                                            }
                                            if ($typeField === 'datetime') {
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
                            }
                        }
                    }
                }
            }
        });
    }
}
