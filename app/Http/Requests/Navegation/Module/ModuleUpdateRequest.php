<?php

namespace App\Http\Requests\Navegation\Module;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ModuleUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:modules,id'],
            'name' => ['required', 'string', 'max:20', 'unique:modules,name,' . $this->route('id') . ',id', 'regex:/^[a-z]+$/'],
            'icon' => ['required', 'string', 'max:40', 'regex:/^cil[A-Z][a-zA-Z]*$/']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'regex' => 'Formato inválido. ',
            'string' => 'Debe ser una cadena de texto.',
            'unique' => 'Ya está registrado.',
            'max' => 'Se permite máximo :max caracteres.'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre del módulo',
            'icon' => 'Icono del módulo',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
