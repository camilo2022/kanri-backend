<?php

namespace App\Http\Requests\Location\Country;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CountrySettingsRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:countries,id'],
            'settings' => ['required', 'array'],
            'settings.validations' => ['present', 'array'],
            'settings.validations.*' => ['required', 'array'],
            'settings.validations.*.id' => ['required', 'numeric', 'distinct'],
            'settings.validations.*.regex' => ['required', 'string'],
            'settings.validations.*.message' => ['required', 'string', 'max:255'],
            'settings.validations.*.created_at' => ['required', 'date'],
            'settings.validations.*.updated_at' => ['nullable', 'date'],
            'settings.validations.*.deleted_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach ($this->input('settings.validations', []) as $i => $v) {
            $n = $i + 1;
            $base = "settings.validations.$i";
            $attributes["$base.id"] = "Id (validación n° $n)";
            $attributes["$base.regex"] = "Expresión regular (validación n° $n)";
            $attributes["$base.message"] = "Mensaje (validación n° $n)";
            $attributes["$base.created_at"] = "Fecha de creación (validación n° $n)";
            $attributes["$base.updated_at"] = "Fecha de actualización (validación n° $n)";
            $attributes["$base.deleted_at"] = "Fecha de eliminación (validación n° $n)";
        }

        return [
            'id' => 'Identificador del país',
            'settings' => 'Configuración',
            'settings.validations' => 'Validaciones del teléfono',
            ...$attributes,
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $seen = [];

            foreach ($this->input('settings.validations', []) as $i => $v) {
                $regex = $v['regex'] ?? null;

                if (! is_string($regex) || @preg_match($regex, '') === false) {
                    $validator->errors()->add("settings.validations.$i.regex", 'Debe ser una expresión regular válida.');

                    continue;
                }

                if (isset($seen[$regex])) {
                    $validator->errors()->add("settings.validations.$i.regex", 'Ya ha sido tomado en la validación n° '.($seen[$regex] + 1).'.');
                } else {
                    $seen[$regex] = $i;
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
