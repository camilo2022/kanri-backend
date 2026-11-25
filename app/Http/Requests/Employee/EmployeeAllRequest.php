<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;

use App\Models\Position;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeAllRequest extends FormRequest
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
        return ['employees.all', 'users.store', 'workflow.processes.settings', 'workflow.processes.subprocesses.settings', 'workflow.processes.subprocesses.operations.settings'];
    }

    public function rules(): array
    {
        return [
            'position_id' => ['nullable', 'numeric', 'exists:subitems,id,item_id,'.Position::ITEM_ID],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Employee::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
            'with_user' => ['nullable', 'boolean']
        ];
    }

    public function attributes(): array
    {
        return [
            'position_id' => 'Cargo',
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
            'with_trashed' => 'Registros inactivos',
            'with_user' => 'Usuario asociado'

        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
        if ($this->has('with_user')) {
            $this->merge(['with_user' => $this->boolean('with_user')]);
        }
    }
}
