<?php

namespace App\Http\Requests\Collection;

use App\Models\Collection;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CollectionUpdateRequest extends FormRequest
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
            'id' => ['required', 'numeric', 'string', 'exists:subitems,id,item_id,' . Collection::ITEM_ID],
            'name' => ['required', 'regex:/^C\d(19\d{2}|20([0-1]\d|2[0-6]))$/', 'string', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . Collection::ITEM_ID],
            'description' => ['required', 'string', 'max:255', 'uppercase'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'array' => 'Debe ser una lista de datos.',
            'numeric' => 'Debe ser un dato númerico.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'uppercase' => 'Debe de ingresar mayúsculas.',
            'regex' => 'Formato inválido. Debe iniciar con C y contener 5 números (los últimos 4 corresponden al año).'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la colección',
            'name' => 'Nombre',
            'description' => 'Descripción',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
