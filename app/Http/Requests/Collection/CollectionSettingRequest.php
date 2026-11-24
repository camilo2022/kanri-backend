<?php

namespace App\Http\Requests\Collection;

use App\Models\Collection;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CollectionSettingRequest extends FormRequest
{
    /** Campos de la colección cuyo formato se configura; el nombre es la base de los marcadores de los otros dos. */
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
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'id' => ['required', 'in:'.Collection::ITEM_ID],
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
            'id.in' => 'Solo se configura el ítem de colecciones.',
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
        // Un regex mal escrito haría fallar a todas las colecciones: se prueba antes de guardarlo. Los marcadores
        // `{parte}` de la descripción y el código deben ser grupos con nombre del regex del nombre: (?<parte>...)
        $validator->after(function ($validator) {
            $name_regex = $this->input('settings.formats.name.regex');
            preg_match_all('/\(\?P?<([A-Za-z_]\w*)>/', (string) $name_regex, $groups);

            foreach (array_keys(self::FIELDS) as $field) {
                $regex = $this->input("settings.formats.$field.regex");
                if (blank($regex)) continue;

                preg_match_all('/\{([A-Za-z_]\w*)\}/', (string) $regex, $used);
                $missing = array_diff($used[1], $groups[1]);

                if (! is_string($regex) || @preg_match(preg_replace('/\{([A-Za-z_]\w*)\}/', '0', $regex), '') === false) {
                    $validator->errors()->add("settings.formats.$field.regex", 'Debe ser una expresión regular válida, con delimitadores (por ejemplo /^C\d{4}$/).');
                } elseif ($field === 'name' && $used[1]) {
                    $validator->errors()->add("settings.formats.$field.regex", 'El nombre no puede usar marcadores {…}: sus partes (?<nombre>…) son las que usan la descripción y el código.');
                } elseif ($missing) {
                    $validator->errors()->add("settings.formats.$field.regex", 'El regex del nombre no tiene la parte {'.implode('}, {', $missing).'}: defínela con (?<'.reset($missing).'>…).');
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
