<?php

namespace App\Http\Requests\BootType;

use App\Models\BootType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BootTypeUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:subitems,id,item_id,' . BootType::ITEM_ID],
            'name' => ['required', 'regex:/^[A-Z ]+$/', 'string', 'min:5', 'max:50', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . BootType::ITEM_ID],
            'description' => ['required', 'regex:/^[A-Z ]+$/', 'max:255']
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
            'regex' => 'Formato Inválido. El campo debe estar en mayúsculas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del tipo de bota',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
