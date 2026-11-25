<?php

namespace App\Http\Requests\OrganizationalStructure\Position;

use App\Models\Position;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PositionAuthorizationAssignRequest extends FormRequest
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
        return ['organizational_structure.areas.positions.authorization.assign'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Position::ITEM_ID],
            'permission_id' => ['required', 'numeric', 'exists:permissions,id', 'unique:model_has_permissions,permission_id,NULL,NULL,model_type,' . Position::class . ',model_id,' . $this->route('id')]
        ];
    }

    public function messages(): array
    {
        return [
            'permission_id.unique' => 'El cargo ya tiene este permiso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del cargo',
            'permission_id' => 'Identificador del permiso'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
