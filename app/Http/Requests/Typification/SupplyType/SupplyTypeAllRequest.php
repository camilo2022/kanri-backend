<?php

namespace App\Http\Requests\Typification\SupplyType;

use App\Models\SupplyType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplyTypeAllRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['typification.supply_types.all', 'workflow.processes.settings', 'workflow.processes.subprocesses.settings', 'workflow.processes.subprocesses.operations.settings'];
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(SupplyType::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
            'in_technical_sheet' => ['nullable', 'boolean'],
            'in_production_order' => ['nullable', 'boolean'],
            'without_supplies' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
            'with_trashed' => 'Registros inactivos',
            'in_technical_sheet' => 'Aplica a Ficha Técnica',
            'without_supplies' => 'Sin insumos',
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
        if ($this->has('without_supplies')) {
            $this->merge(['without_supplies' => $this->boolean('without_supplies')]);
        }
    }
}
