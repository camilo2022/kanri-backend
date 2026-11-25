<?php

namespace App\Http\Requests\Size;

use App\Models\Size;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SizeSettingRequest extends FormRequest
{
    /** Campos de la talla cuyo formato se configura; solo se revisan en las tallas de producto terminado. */
    public const FIELDS = ['name' => 'Nombre', 'description' => 'Descripción', 'code' => 'Código'];

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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['sizes.settings'];
    }

    public function rules(): array
    {
        $rules = [
            'id' => ['required', 'in:'.Size::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.formats' => ['required', 'array'],
        ];

        foreach (array_keys(self::FIELDS) as $field) {
            $rules["settings.formats.$field"] = ['present', 'array'];
            $rules["settings.formats.$field.regex"] = ['nullable', 'string', 'max:255'];
            $rules["settings.formats.$field.message"] = ['nullable', 'required_with:settings.formats.'.$field.'.regex', 'string', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'id.in' => 'Solo se configura el ítem de tallas.',
            'settings.formats.*.message.required_with' => 'Escribe el mensaje que verá quien no cumpla el formato.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (self::FIELDS as $field => $label) {
            $attributes["settings.formats.$field"] = "Formato ($label)";
            $attributes["settings.formats.$field.regex"] = "Expresión regular ($label)";
            $attributes["settings.formats.$field.message"] = "Mensaje ($label)";
        }

        return [
            'id' => 'Identificador del ítem',
            'settings' => 'Configuraciones',
            'settings.formats' => 'Formatos',
            ...$attributes,
        ];
    }

    public function withValidator($validator)
    {
        // Un regex mal escrito haría fallar a todas las tallas: se prueba antes de guardarlo, con `{talla}` ya reemplazado
        $validator->after(function ($validator) {
            foreach (array_keys(self::FIELDS) as $field) {
                $regex = $this->input("settings.formats.$field.regex");
                if (blank($regex)) continue;

                if ($field === 'code' && is_string($regex) && str_contains($regex, '{talla}')) {
                    $validator->errors()->add("settings.formats.$field.regex", 'El código no puede usar {talla}: el código es la talla que reemplaza a los demás.');
                } elseif (! is_string($regex) || @preg_match(str_replace('{talla}', 'X', $regex), '') === false) {
                    $validator->errors()->add("settings.formats.$field.regex", 'Debe ser una expresión regular válida, con delimitadores (por ejemplo /^T\d{2}$/).');
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
