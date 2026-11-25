<?php

namespace App\Http\Requests\Size;

use App\Models\Item;
use App\Models\Size;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SizeUpdateRequest extends FormRequest
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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['sizes.update'];
    }

    public function rules(): array
    {
        $finished = $this->boolean('settings.finished_product');

        // Formato de cada campo configurado en el ítem de tallas (Tallas → Configurar), solo para producto terminado;
        // sin regex no se revisa. Al editar solo se revisa lo que cambió, para que una talla antigua siga editable si
        // cambia la nomenclatura; también se revisa todo si la talla pasa a ser de producto terminado, y nombre y
        // descripción si su regex usa `{talla}` (se reemplaza por el código enviado, escapado) y el código cambió.
        $formats = Item::find(Size::ITEM_ID)?->settings?->formats;
        $size = Size::withTrashed()->find($this->route('id'));
        $stored = [
            'name' => $size?->name,
            'description' => $size?->description,
            'code' => $size->settings->code ?? null,
        ];
        $sent = ['name' => $this->input('name'), 'description' => $this->input('description'), 'code' => $this->input('settings.code')];
        $was_finished = (bool) ($size->settings->finished_product ?? false);
        $regex = function (string $field) use ($formats, $size, $stored, $sent, $was_finished) {
            $pattern = $formats->{$field}->regex ?? null;
            if (blank($pattern)) return null;

            $uses_code = str_contains($pattern, '{talla}');
            $check = ! $size
                || $stored[$field] !== $sent[$field]
                || ! $was_finished
                || ($uses_code && $stored['code'] !== $sent['code']);

            return $check ? 'regex:' . str_replace('{talla}', preg_quote((string) $sent['code'], $pattern[0]), $pattern) : null;
        };

        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . Size::ITEM_ID],
            'name' => array_values(array_filter([
                'required',
                'string',
                'min:1',
                'max:50',
                $finished ? $regex('name') : null,
                'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . Size::ITEM_ID,
            ])),
            'description' => array_values(array_filter([
                'required',
                'string',
                'max:50',
                $finished ? $regex('description') : null,
            ])),
            'settings' => ['required', 'array'],
            // El controlador guarda `settings` completo: sin el código se perdería al editar
            'settings.code' => array_values(array_filter([
                'required',
                'string',
                $finished ? $regex('code') : null,
                Rule::unique('subitems', 'settings->code')->where('item_id', Size::ITEM_ID)->ignore($this->route('id')),
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
            'id' => 'Identificador de la talla',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.code' => 'Código',
            'settings.finished_product' => 'Es producto terminado'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);

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
}
