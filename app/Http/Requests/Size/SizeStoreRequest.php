<?php

namespace App\Http\Requests\Size;

use App\Models\Item;
use App\Models\Size;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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

    public function rules(): array
    {
        $item = Item::find(Size::ITEM_ID);
        return [
            'name' => array_values([
                'required',
                $this->boolean('settings.finished_product') ? $item?->settings?->finished_product?->name : '',
                'string',
                'min:2',
                'max:50',
                'unique:subitems,name,NULL,id,item_id,' . Size::ITEM_ID
            ]),
            'description' => array_values(['required', $this->boolean('settings.finished_product') ? $item?->settings?->finished_product?->description : '', 'max:50']),
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'numeric', 'digits_between:2,5'],
            'settings.finished_product' => ['required', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'name.regex' => 'Debe ser una talla válida o un número par de 2 dígitos.',
            'description.regex' => 'Debe iniciar con "T" seguido de al menos 2 números.',
            'digits_between' => 'Debe tener entre 2 y 5 dígitos.',
        ];
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
