<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use App\Models\Employee;
use App\Services\ProductivityDetailService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Registro de trabajo en curso de un puesto: tiempos, empleados, paradas, la referencia que se trabaja (orden y regla),
 * los destinos con la curva (solo si el puesto crea ingresos, para digitar las unidades al finalizar), los contadores
 * del lote (procesos por unidades) y el resumen si se finalizara ahora. El puesto es su `model` y el proceso, el del
 * turno.
 */
class ProductivityDetailResource extends JsonResource
{
    public function toArray($request)
    {
        $service = app(ProductivityDetailService::class);
        $this->loadMissing(['productivity' => ['model'], 'model', 'stops', 'employees' => ['person'], 'rule', 'production_order']);

        $workstation = $this->model;
        $process = $this->productivity->model;
        $creates_ingress = $service->createsIngress($process, $workstation);
        $counts_at_finish = $service->countsAtFinish($process, $workstation);
        $with_plus = $service->countsWithPlus($process, $workstation);
        $total_at_finish = $service->totalAtFinish($process, $workstation);
        $open = $service->openStop($this->resource);

        $stop = fn ($row) => [
            'id' => $row->id,
            'stop_id' => $row->model_id,
            'name' => $row->settings->name ?? null,
            'type' => $row->settings->type ?? null,
            'start' => $row->settings->start ?? null,
            'end' => $row->settings->end ?? null,
            'automatic' => $row->settings->automatic ?? false,
        ];

        return [
            'productivity_detail' => array_merge(
                [
                    'id' => $this->id,
                    'start' => $this->start->format('Y-m-d H:i:s'),
                    'scanned_at' => $this->settings->scanned_at ?? null,
                    'shift_end' => $this->productivity->endsAt()->format('Y-m-d H:i:s'),
                    'employees' => $this->employees->map(fn ($employee) => array_merge($service->employeePayload($employee), [
                        'borrowed_from' => json_decode($employee->pivot->settings ?? '[]', true)['borrowed_from'] ?? null,
                        // A quién reemplazó: el programado que estaba ocupado en otro trabajo
                        'replaces' => ($replaced = json_decode($employee->pivot->settings ?? '[]', true)['replaces'] ?? null)
                            ? $service->employeePayload(Employee::with('person')->find($replaced))
                            : null,
                    ]))->values(),
                    'stops' => $this->stops->map($stop)->values(),
                    'open_stop' => $open ? $stop($open) : null,
                    'creates_ingress' => $creates_ingress,
                    // ETIQUETADO por unidades precarga lo que le falta a la curva; por lote, la curva de la orden
                    'destinations' => $creates_ingress
                        ? ($counts_at_finish ? $service->pendingCurve($this->production_order, $workstation) : $service->destinations($this->production_order))
                        : [],
                    // Medición del puesto (la suya o la heredada); con el +1, contadores del lote y lo que lleva el registro
                    'measurement' => $service->measurementOf($workstation),
                    'counts_at_finish' => $counts_at_finish,
                    // Sin +1: digitan el total al finalizar, hasta lo que le falta al lote en el paso
                    'total_at_finish' => $total_at_finish,
                    'finish_cap' => $total_at_finish ? $service->finishCap($this->resource, $process) : null,
                    'counters' => $with_plus || $total_at_finish ? $service->counters($this->production_order, $process, $workstation) : null,
                    'produced' => $service->producedBy($this->resource),
                    // Hasta dónde puede llegar según lo que ya soltó el puesto programado antes
                    'units_limit' => $this->settings->scanned_at ?? null ? $service->unitsLimitOf($this->resource, $process) : null,
                    'hour' => $with_plus ? $service->hourStatus($this->resource) : null,
                    'scheduled' => $this->settings->scheduled ?? null,
                    // Por ojal o por golpe: el tiempo de la regla se multiplica por este factor
                    'multiplier' => $service->multiplierPayload($this->production_order, $workstation, $this->settings->multiplier ?? null),
                    'siblings' => $with_plus || $total_at_finish ? $service->siblings($this->resource) : [],
                    // El mismo grupo trabaja a la vez otros puestos del lote (PARCHE + RELOJERA): el +1 suma en todos
                    'companions' => $service->companions($this->resource)->map(fn ($row) => [
                        'id' => $row->id,
                        'name' => $row->model->name,
                        'produced' => $service->producedBy($row),
                        'scheduled' => $row->settings->scheduled ?? null,
                        'counters' => $service->counters($this->production_order, $process, $row->model),
                        'units_limit' => $service->unitsLimitOf($row, $process),
                    ])->values(),
                    // Resumen si se finalizara ahora (inicio, fin oficial, duración, personas, paradas y tiempos), para
                    // el modal de finalizar; al finalizar se recalcula
                    'preview' => $service->summary($this->resource, $service->officialEnd($this->resource)),
                ],
                // La capacidad con la que se trabaja: la copiada al iniciar (por lote) o, por unidades, la de la regla para
                // las personas de este registro (no las libres del puesto: las que trabajan ya están ocupadas aquí)
                $service->referenceData(
                    $this->production_order,
                    $workstation,
                    $process,
                    $this->rule,
                    (array) ($this->settings->capacity ?? [])
                        ?: ($this->rule ? $service->capacity($this->rule, $workstation, max(1, $this->employees->count())) : null)
                )
            ),
        ];
    }
}
