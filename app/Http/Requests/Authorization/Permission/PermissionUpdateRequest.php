<?php

namespace App\Http\Requests\Authorization\Permission;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class PermissionUpdateRequest extends FormRequest
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
            'role_id' => ['required', 'numeric', 'exists:roles,id'],
            'id' => ['required', 'exists:permissions,id'],
            'name' => ['required', 'string', 'unique:permissions,name,'.$this->route('id').',id', 'max:80', 'regex:/^[a-z._]+$/'],
            'title' => ['required', 'string', 'unique:permissions,title,'.$this->route('id').',id', 'max:100'],
            'description' => ['required', 'string', 'unique:permissions,description,'.$this->route('id').',id', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => 'Formato inválido. Solo permite letras minúsculas, punto y guion bajo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'role_id' => 'Identificador del rol',
            'id' => 'Identificador del permiso',
            'name' => 'Nombre del permiso',
            'title' => 'Título del permiso',
            'description' => 'Descripción del permiso',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
