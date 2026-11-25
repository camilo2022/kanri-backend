<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserAuthorizationRemoveRequest extends FormRequest
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
        return ['users.authorization.remove'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric', 'exists:users,id'],
            'permission_id' => ['required', 'numeric', 'exists:permissions,id', 'exists:model_has_permissions,permission_id,model_type,'.User::class.',model_id,'.$this->route('id')]
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del usuario',
            'permission_id' => 'Identificador del permiso'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
