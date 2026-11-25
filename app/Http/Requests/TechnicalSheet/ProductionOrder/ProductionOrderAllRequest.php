<?php

namespace App\Http\Requests\TechnicalSheet\ProductionOrder;

use App\Models\ProductionOrder;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductionOrderAllRequest extends FormRequest
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
        return ['technical_sheets.production_orders.all'];
    }

    public function rules(): array
    {

        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(ProductionOrder::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
            'technical_sheet_id' => ['nullable', 'numeric', 'exists:technical_sheets,id'],
            'processes_id' => ['nullable', 'array', 'min:1'],
            'processes_id.*' => ['numeric', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            // Programación del turno: solo los lotes que se pueden programar en el proceso (ProductionOrder::programmableIn)
            'process_id' => ['nullable', 'numeric', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            // Puesto de la programación: solo los lotes que se trabajan ahí (con process_id)
            'model_type' => ['nullable', 'required_with:model_id', 'in:'.implode(',', [Process::class, Subprocess::class, Operation::class])],
            'model_id' => ['nullable', 'required_with:model_type', 'numeric', 'exists:subitems,id,deleted_at,NULL'],
            // Programación: también los lotes a los que solo les falta el anterior en la cadena, con cuál les falta
            // (previous_missing, sin contar las filas sin iniciar del turno productivity_id)
            'pending_previous' => ['nullable', 'boolean'],
            'productivity_id' => ['nullable', 'integer', 'exists:productivity,id'],
            // Programación en cadena (CORTE): los lotes a los que les falta cualquier puesto de la cadena, no solo el indicado
            'chain' => ['nullable', 'boolean'],
            // Cronograma de producción: cada ficha con la tipología de cada proceso
            'with_typologies' => ['nullable', 'boolean'],
            'start_date' => ['required_without_all:technical_sheet_id,process_id', 'nullable', 'date'],
            'end_date' => ['required_without_all:technical_sheet_id,process_id', 'nullable', 'date', 'after_or_equal:start_date'],
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
            'process_id' => 'Proceso a programar',
            'model_type' => 'Tipo de puesto',
            'model_id' => 'Puesto de trabajo',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
        $this->merge(['technical_sheet_id' => $this->route('technical_sheet_id')]);
    }
}
