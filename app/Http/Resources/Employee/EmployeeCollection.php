<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Employee::class, $this->collection->pluck('id')->all());
        return [
            'employees' => $this->collection->map(function ($employee) use ($audited) {
                return [
                    'id' => $employee->id,
                    'has_audits' => isset($audited[$employee->id]),
                    'person_id' => $employee->person_id,
                    'operation_center' => $employee->operation_center,
                    'position_id' => $employee->position_id,
                    'risk_manager_id' => $employee->risk_manager_id,
                    'health_entity_id' => $employee->health_entity_id,
                    'pension_fund_id' => $employee->pension_fund_id,
                    'compensation_fund_id' => $employee->compensation_fund_id,
                    'workstation_id' => $employee->workstation_id,
                    'start_date' => $employee->start_date?->format('Y-m-d'),
                    'end_date' => $employee->end_date?->format('Y-m-d'),
                    'created_at' => $this->formatDate($employee->created_at),
                    'updated_at' => $this->formatDate($employee->updated_at),
                    'deleted_at' => $this->formatDate($employee->deleted_at),
                    'person' => $employee->person,
                    'position' => $employee->position,
                    'user' => $employee->user,
                    'risk_manager' => $employee->risk_manager,
                    'health_entity' => $employee->health_entity,
                    'pension_fund' => $employee->pension_fund,
                    'compensation_fund' => $employee->compensation_fund,
                    'workstation' => $employee->workstation,
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function paginationMeta()
    {
        return [
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
        ];
    }
}
