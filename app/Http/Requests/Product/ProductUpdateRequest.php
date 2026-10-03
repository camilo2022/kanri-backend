<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Subitem;
use App\Models\Trademark;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductUpdateRequest extends FormRequest
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
        $trademark = Subitem::find($this->trademark_id);

        return [
            'id' => ['required', 'exists:products,id'],
            'code' => ['required', 'unique:products,code,' . $this->route('id') . ',id'],
            'trademark_id' => ['required', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'subcategory_id' => ['required', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID]
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
            'exists' => 'No está registrado.',
            'uppercase' => 'Debe de ingresar mayúsculas.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la linea',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
