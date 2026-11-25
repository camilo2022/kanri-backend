<?php

namespace App\Http\Requests\Workflow\Process;

use App\Models\Process;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessAllRequest extends FormRequest
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
        return ['workflow.processes.all', 'users.workflow.assign', 'employees.store', 'employees.update'];
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Process::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
            'process_id' => ['nullable', 'numeric', 'exists:subitems,id,item_id,' . Process::ITEM_ID],
            'in_technical_sheet' => ['nullable', 'boolean'],
            'in_production_order' => ['nullable', 'boolean'],
            'with_typologies' => ['nullable', 'boolean'],
            'with_trashed_children' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'in_technical_sheet' => 'Aplica a Ficha Técnica',
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por página.',
            'page' => 'N° de página.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
            'with_trashed' => 'Registros inactivos',
            'with_trashed_children' => 'Subprocesos y operaciones inactivos',
            'process_id' => 'Identificador del proceso.',
            'in_technical_sheet' => 'Aplica a Ficha Técnica'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
        if ($this->has('in_technical_sheet')) {
            $this->merge(['in_technical_sheet' => $this->boolean('in_technical_sheet')]);
        }
        if ($this->has('in_production_order')) {
            $this->merge(['in_production_order' => $this->boolean('in_production_order')]);
        }
        if ($this->has('with_trashed_children')) {
            $this->merge(['with_trashed_children' => $this->boolean('with_trashed_children')]);
        }
    }
}
