<?php

namespace App\Http\Requests\Size;

use App\Models\Item;
use App\Models\Size;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SizeStoreRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        // Nombre, descripción y código se normalizan: sin espacios de más y en mayúsculas
        if (is_string($this->input('name'))) {
            $this->merge(['name' => strtoupper(trim($this->input('name')))]);
        }
        if (is_string($this->input('description'))) {
            $this->merge(['description' => strtoupper(preg_replace('/\s+/', ' ', trim($this->input('description'))))]);
        }

        $settings = $this->input('settings');
        if (is_array($settings) && isset($settings['code']) && is_string($settings['code'])) {
            $settings['code'] = strtoupper(trim($settings['code']));
            $this->merge(['settings' => $settings]);
        }
    }

    public function rules(): array
    {
        $finished = $this->boolean('settings.finished_product');

        // Formato de cada campo configurado en el ítem de tallas (Tallas → Configurar), solo para producto terminado;
        // sin regex no se revisa. `{talla}` en el regex se reemplaza por el código enviado (escapado): así nombre y
        // descripción salen de la talla
        $formats = Item::find(Size::ITEM_ID)?->settings?->formats;
        $code = (string) $this->input('settings.code');
        $regex = fn (string $field) => filled($formats->{$field}->regex ?? null)
            ? 'regex:' . str_replace('{talla}', preg_quote($code, $formats->{$field}->regex[0]), $formats->{$field}->regex)
            : null;

        return [
            'name' => array_values(array_filter([
                'required',
                'string',
                'min:1',
                'max:50',
                $finished ? $regex('name') : null,
                'unique:subitems,name,NULL,id,item_id,' . Size::ITEM_ID,
            ])),
            // Nombre y descripción son datos para mostrar en el listado de tallas; la identidad de la talla es su código
            'description' => array_values(array_filter([
                'required',
                'string',
                'max:50',
                $finished ? $regex('description') : null,
            ])),
            'settings' => ['required', 'array'],
            'settings.code' => array_values(array_filter([
                'required',
                'string',
                $finished ? $regex('code') : null,
                Rule::unique('subitems', 'settings->code')->where('item_id', Size::ITEM_ID),
            ])),
            'settings.finished_product' => ['required', 'boolean']
        ];
    }

    public function messages(): array
    {
        // El mensaje del regex lo escribe quien configura el formato (Tallas → Configurar)
        $formats = Item::find(Size::ITEM_ID)?->settings?->formats;

        return array_filter([
            'name.regex' => $formats->name->message ?? null,
            'description.regex' => $formats->description->message ?? null,
            'settings.code.regex' => $formats->code->message ?? null,
            'settings.code.unique' => 'Ya existe una talla con este código.',
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.code' => 'Código',
            'settings.finished_product' => 'Es producto terminado'
        ];
    }
}
