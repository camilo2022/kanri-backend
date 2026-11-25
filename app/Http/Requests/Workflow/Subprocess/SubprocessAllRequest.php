<?php

namespace App\Http\Requests\Workflow\Subprocess;

use App\Models\Subprocess;
use App\Services\PermissionService;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubprocessAllRequest extends FormRequest
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
        return ['workflow.processes.subprocesses.all'];
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Subprocess::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'process_id' => 'Identificador del proceso.',
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
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
    }
}
