<?php

namespace App\Http\Requests\Employee;

use App\Models\CompensationFund;
use App\Models\HealthEntity;
use App\Models\Operation;
use App\Models\PensionFund;
use App\Models\Position;
use App\Models\Process;
use App\Models\RiskManager;
use App\Models\Subitem;
use App\Models\Subprocess;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeStoreRequest extends FormRequest
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
        return ['employees.store'];
    }

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'numeric', 'exists:people,id,deleted_at,NULL', 'unique:employees,person_id'],
            'operation_center' => ['nullable', 'string'],
            'position_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Position::ITEM_ID.',deleted_at,NULL'],
            'risk_manager_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.RiskManager::ITEM_ID.',deleted_at,NULL'],
            'health_entity_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.HealthEntity::ITEM_ID.',deleted_at,NULL'],
            'pension_fund_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.PensionFund::ITEM_ID.',deleted_at,NULL'],
            'compensation_fund_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.CompensationFund::ITEM_ID.',deleted_at,NULL'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:start_date'],
            'workstation_id' => ['nullable', 'integer', 'exists:subitems,id,deleted_at,NULL'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->workstation_id && ! Subitem::where('id', $this->workstation_id)
                ->whereIn('item_id', [Process::ITEM_ID, Subprocess::ITEM_ID, Operation::ITEM_ID])
                ->exists()) {
                $validator->errors()->add('workstation_id', 'El puesto debe ser un proceso, subproceso u operación.');
            }

            if ($validator->errors()->isNotEmpty() || ! $this->filled('workstation_id')) {
                return;
            }

            $id = $this->integer('workstation_id');
            $workstation = Operation::find($id) ?? Subprocess::find($id) ?? Process::find($id);

            $children = match (true) {
                $workstation instanceof Process && $workstation->subprocesses()->exists() => 'subprocesos',
                $workstation instanceof Subprocess && $workstation->operations()->exists() => 'operaciones',
                default => null,
            };

            if ($children) {
                $validator->errors()->add('workstation_id', "{$workstation->name} tiene {$children}; elige uno de ellos.");
            }
        });
    }

    public function attributes(): array
    {
        return [
            'person_id' => 'Persona',
            'operation_center' => 'Centro de operaciones',
            'position_id' => 'Cargo',
            'risk_manager_id' => 'Administradora de Riesgos',
            'health_entity_id' => 'Entidad de Salud',
            'pension_fund_id' => 'Fondo de pensión',
            'compensation_fund_id' => 'Caja de compensación',
            'start_date' => 'Fecha inicio',
            'end_date' => 'Fecha fin',
            'workstation_id' => 'Puesto de trabajo',
        ];
    }
}
