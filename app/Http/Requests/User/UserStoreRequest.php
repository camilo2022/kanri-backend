<?php

namespace App\Http\Requests\User;

use App\Models\Employee;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserStoreRequest extends FormRequest
{
    public function withValidator($validator)
    {
        // El usuario recibe los roles y permisos del cargo del empleado: sin cargo activo no hay de dónde tomarlos
        $validator->after(function ($validator) {
            if ($validator->errors()->has('employee_id')) {
                return;
            }

            if (! Employee::whereKey($this->input('employee_id'))->whereHas('position')->exists()) {
                $validator->errors()->add('employee_id', 'El empleado no tiene un cargo activo.');
            }
        });
    }

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
        return ['users.store'];
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'numeric', 'exists:employees,id,deleted_at,NULL', 'unique:users,employee_id'],
            'password' => ['required', 'string', 'min:8', 'regex:~^(?=.*\p{Ll})(?=.*\p{Lu})(?=.*\d)(?=.*[@$!%*#?&._-]).{8,}$~u', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8', 'regex:~^(?=.*\p{Ll})(?=.*\p{Lu})(?=.*\d)(?=.*[@$!%*#?&._-]).{8,}$~u',]

        ];
    }

    public function messages(): array
    {
        return [
            'regex' => "Formato inválido:\n- Al menos una letra mayúscula.\n- Al menos una letra minúscula.\n- Al menos un número.\n- Al menos un carácter especial.",
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'Empleado',
            'password' => 'Contraseña del usuario',
            'password_confirmation' => 'Confirmación contraseña del usuario',
        ];
    }
}
