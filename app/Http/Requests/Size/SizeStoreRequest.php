<?php

namespace App\Http\Requests\Size;

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
        return [
            'name' => ['required', 'regex:/^(?:\d[02468]|XXS|XS|S|M|L|XL|XXL|U)$/', 'string', 'min:2', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . Size::ITEM_ID],
            'description' => ['required', 'regex:/^T\d{2,}$/', 'max:50']
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
            'name.regex' => 'Formato Inválido. Use números pares de dos dígitos o tallas como XS, M, XL, U.',
            'description.regex' => 'Formato Inválido. Debe iniciar con T.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }
}
