<?php

namespace App\Http\Requests\Authorization\Role;

use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleStoreRequest extends FormRequest
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
        return ['authorization.roles.store'];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'unique:roles,name', 'max:80', 'regex:/^[a-z_]+$/'],
            'title' => ['required', 'string', 'unique:roles,title', 'max:100'],
            'description' => ['required', 'string', 'unique:roles,description', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => 'Formato inválido. Solo permite letras minúsculas y sin espacios.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre del rol',
            'title' => 'Título del rol',
            'description' => 'Descripción del rol',
        ];
    }
}
