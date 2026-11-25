<?php

namespace App\Http\Requests\Authorization\Role;

use App\Models\Role;
use App\Services\PermissionService;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleAllRequest extends FormRequest
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
        return ['authorization.roles.all', 'navegation.modules.submodules.store', 'navegation.modules.submodules.update', 'users.authorization.assign', 'users.authorization.remove', 'authorization.permissions.store', 'authorization.permissions.update', 'organizational_structure.areas.positions.authorization.assign', 'organizational_structure.areas.positions.authorization.remove'];
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Role::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc']
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'Debe ser un registro válido de busqueda.',
            'column.string' => 'Debe ser una columna válida.',
            'column.in' => 'El valor de la columna no es válido.',
            'dir.string' => 'Debe ser un valor de orden válido.',
            'dir.in' => 'El valor del orden no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',

        ];
    }
}
