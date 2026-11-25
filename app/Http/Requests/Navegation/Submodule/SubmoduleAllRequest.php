<?php

namespace App\Http\Requests\Navegation\Submodule;

use App\Models\Submodule;
use App\Services\PermissionService;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmoduleAllRequest extends FormRequest
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
        return ['navegation.modules.submodules.all'];
    }

    public function rules(): array
    {
        return [
            'module_id' => ['required', 'exists:modules,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Submodule::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'module_id.exists' => 'No hay ningún registro.',
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
            'module_id' => 'Identificador del módulo.',
            'per_page' => 'Registros por pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
            'with_trashed' => 'Registros inactivos'

        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['module_id' => $this->route('module_id')]);
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
    }
}
