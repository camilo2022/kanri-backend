<?php

namespace App\Http\Requests\Size;

use App\Models\Size;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . Size::ITEM_ID],
            'name' => ['required', 'regex:/^(?:\d{2}|X{0,2}(?:S|M|L)|XXL)$/', 'min:2', 'max:50', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . Size::ITEM_ID],
            'description' => ['required', 'regex:/^T\d{2,}$/', 'max:255']
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
            'regex' => 'Formato Inválido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la talla',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
