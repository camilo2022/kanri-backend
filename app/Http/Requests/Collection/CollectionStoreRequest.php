<?php

namespace App\Http\Requests\Collection;

use App\Models\Collection;
use App\Models\Item;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CollectionStoreRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Formato de cada campo configurado en el ítem de colecciones (Colecciones → Configurar); sin regex no se revisa.
        // Las partes con nombre del regex del nombre, (?<numero>…), reemplazan a {numero} en la descripción y el código;
        // si el nombre no cumple su formato, los campos que dependen de él no se revisan (ya sale el error del nombre)
        $formats = Item::find(Collection::ITEM_ID)?->settings?->formats;
        $parts = [];
        if (filled($formats->name->regex ?? null) && @preg_match($formats->name->regex, (string) $this->input('name'), $matches) === 1) {
            $parts = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        $regex = function (string $field) use ($formats, $parts) {
            $pattern = $formats->{$field}->regex ?? null;
            if (blank($pattern)) return null;

            preg_match_all('/\{([A-Za-z_]\w*)\}/', $pattern, $used);
            if (array_diff($used[1], array_keys($parts))) return null;

            return 'regex:' . preg_replace_callback('/\{([A-Za-z_]\w*)\}/', fn ($m) => preg_quote($parts[$m[1]], $pattern[0]), $pattern);
        };

        return [
            'name' => array_values(array_filter(['required', 'string', $regex('name'), 'unique:subitems,name,NULL,id,item_id,' . Collection::ITEM_ID])),
            'description' => array_values(array_filter(['required', 'string', 'max:255', 'uppercase', $regex('description')])),
            'settings' => ['required', 'array'],
            'settings.code' => array_values(array_filter(['required', 'string', $regex('code')])),
            'settings.start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'settings.end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:settings.start_date'],
        ];
    }

    public function messages(): array
    {
        // El mensaje del regex lo escribe quien configura el formato (Colecciones → Configurar)
        $formats = Item::find(Collection::ITEM_ID)?->settings?->formats;

        return array_filter([
            'name.regex' => $formats->name->message ?? null,
            'description.regex' => $formats->description->message ?? null,
            'settings.code.regex' => $formats->code->message ?? null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings.code' => 'Código',
            'settings.start_date' => 'Fecha de Inicio',
            'settings.end_date' => 'Fecha de Fin',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $settings = $this->input('settings', []);

            $code = $settings['code'] ?? null;
            $startDate = $settings['start_date'] ?? null;
            $endDate = $settings['end_date'] ?? null;

            $existsCode = Collection::withTrashed()
                ->where('item_id', Collection::ITEM_ID)
                ->where('settings->code', $code)
                ->exists();

            if ($existsCode) {

                $validator->errors()->add(
                    'settings.code',
                    'El código ya está registrado.'
                );
            }

            // El número de la colección es consecutivo dentro de su año (C1…, C2…): debe seguir al último, contando
            // las desactivadas. Se lee de las partes `numero` y `anio` del regex del nombre; sin ellas no se revisa
            $name_regex = Item::find(Collection::ITEM_ID)?->settings?->formats?->name?->regex ?? null;
            if (filled($name_regex) && @preg_match($name_regex, (string) $this->input('name'), $sent) === 1 && isset($sent['numero'], $sent['anio'])) {
                $last = Collection::withTrashed()->pluck('name')
                    ->map(fn ($name) => @preg_match($name_regex, (string) $name, $found) === 1 && ($found['anio'] ?? null) === $sent['anio'] ? (int) ($found['numero'] ?? -1) : null)
                    ->filter(fn ($numero) => $numero !== null)
                    ->max();
                $next = $last === null ? 1 : $last + 1;

                if ((int) $sent['numero'] !== $next) {
                    $validator->errors()->add('name', "El número de la colección es consecutivo: para {$sent['anio']} el siguiente es {$next}.");
                }
            }

            if ($startDate && $endDate) {

                $crossingCollection = Collection::withTrashed()
                    ->where('item_id', Collection::ITEM_ID)
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(settings, '$.start_date')) <= ?",
                            [$endDate]
                        )->whereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(settings, '$.end_date')) >= ?",
                            [$startDate]
                        );
                    })
                    ->exists();

                if ($crossingCollection) {

                    $validator->errors()->add(
                        'settings.start_date',
                        'Ya existe una colección en ese rango de fechas.'
                    );
                }
            }
        });
    }
}
