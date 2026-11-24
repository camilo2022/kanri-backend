<?php

namespace App\Http\Requests\Typification\SupplyType;

use App\Models\Size;
use App\Models\SupplyType;
use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplyTypeSettingRequest extends FormRequest
{
    use ModelTypes;

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
            'id' => ['required', 'exists:subitems,id,item_id,'.SupplyType::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.form' => ['present', 'array'],
            'settings.form.*.field' => ['required', 'string', 'regex:/^[a-z]+(_[a-z]+)*$/'],
            'settings.form.*.label' => ['required', 'string', 'uppercase'],
            'settings.form.*.type' => ['required', 'string'],
            'settings.form.*.rules' => ['array'],
        ];
    }

    public function messages()
    {
        return [
            'regex' => 'Formato inválido. Solo letras minúsculas y guiones bajos (_).',
        ];
    }

    public function attributes()
    {
        $attributes = [];

        foreach ($this->input('settings.form', []) as $i => $field) {
            $n = $i + 1;
            $attributes["settings.form.$i.field"] = "Campo n° $n: nombre";
            $attributes["settings.form.$i.label"] = "Campo n° $n: etiqueta";
            $attributes["settings.form.$i.type"] = "Campo n° $n: tipo de dato";
            $attributes["settings.form.$i.rules"] = "Campo n° $n: reglas";
        }

        return [
            'id' => 'Identificador del tipo de insumo',
            'settings' => 'Configuración',
            'settings.form' => 'Formulario de los insumos',
            ...$attributes,
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $schema = $this->input('settings.form', []);
            $models = collect($this->getModelTypes());

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

                if ($type === 'associated') {
                    $origin = collect($schema)->first(fn ($other, $o) => $o !== $t && ($other['field'] ?? null) === ($s['associated_to'] ?? null));

                    // Solo de un campo de varios valores: si el campo es de uno, lo asociado es del insumo (para eso está Foto)
                    if (! $origin || in_array($origin['type'] ?? null, ['photo', 'associated']) || ($origin['cardinality'] ?? 'single') !== 'multiple') {
                        $validator->errors()->add(
                            "$basePath.associated_to",
                            'Debe ser otro campo del formulario de varios valores.'
                        );
                    }

                    if (isset($s['model']) && ! $models->where('model', $s['model'])->whereNull('param')->count()) {
                        $validator->errors()->add(
                            "$basePath.model",
                            'Debe ser un catálogo de la lista.'
                        );
                    }
                }

                if ($type === 'photo' || ($type === 'associated' && ! isset($s['model']))) {
                    if (! in_array($s['cardinality'] ?? null, ['single', 'multiple'])) {
                        $validator->errors()->add(
                            "$basePath.cardinality",
                            'Valor requerido.'
                        );
                    }
                }

                if (array_key_exists('finished_product', $s) && (($s['model'] ?? null) !== Size::class || ! is_bool($s['finished_product']))) {
                    $validator->errors()->add(
                        "$basePath.finished_product",
                        'Solo aplica a las tallas.'
                    );
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

                    $param = $models->firstWhere('model', $models->firstWhere('model', $s['model'] ?? null)['param'] ?? null);

                    if ($param && ! $param['model']::whereKey($s[$param['field']] ?? null)->exists()) {
                        $validator->errors()->add(
                            "$basePath.{$param['field']}",
                            'Valor requerido.'
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

    protected function passedValidation()
    {
        // Configurar cambia el formulario de los insumos; los indicadores los escribe Editar y aquí se conservan
        $settings = (array) (SupplyType::find($this->route('id'))?->settings ?? []);
        unset($settings['has_variants']);

        // El subtipo de las fotos es fijo (Supply::PHOTO_FILE_SUBTYPE_ID): no se guarda en el campo
        $form = collect($this->input('settings.form'))->map(fn ($field) => collect($field)->except('file_subtype_id')->all())->all();

        $this->merge(['settings' => array_merge($settings, ['form' => $form])]);
    }
}
