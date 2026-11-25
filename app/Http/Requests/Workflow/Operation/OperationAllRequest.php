<?php

namespace App\Http\Requests\Workflow\Operation;

use App\Models\Operation;

use App\Models\Subprocess;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class OperationAllRequest extends FormRequest
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
        return ['workflow.processes.subprocesses.operations.all'];
    }

    public function rules(): array
    {
        return [
            'subprocess_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Subprocess::ITEM_ID],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Operation::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subprocess_id' => 'Subproceso',
            'per_page' => 'Registros por página.',
            'page' => 'N° de página.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
            'with_trashed' => 'Registros inactivos',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'subprocess_id' => $this->route('subprocess_id'),
            'with_trashed' => $this->boolean('with_trashed'),
        ]);
    }
}
