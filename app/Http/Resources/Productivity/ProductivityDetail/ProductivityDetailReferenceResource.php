<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use App\Services\ProductivityDetailService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ficha técnica escaneada en un puesto: la referencia y su regla, los empleados que pueden trabajar, la programación
 * del lote (procesos por unidades), la secuencia de la cadena, los contadores y la hora a la que iniciaría. Recibe
 * production_order, workstation, process y shift.
 */
class ProductivityDetailReferenceResource extends JsonResource
{
    public function toArray($request)
    {
        $service = app(ProductivityDetailService::class);
        $production_order = $this->resource['production_order'];
        $workstation = $this->resource['workstation'];
        $process = $this->resource['process'];
        $shift = $this->resource['shift'];
        // Programables (por unidades, salvo ETIQUETADO): con el +1 o con el total al finalizar
        $by_units = $service->programmable($process, $workstation);

        // Grupos programados con el lote en el puesto (sin iniciar). Con varios (el lote repartido entre dos grupos), la
        // tablet elige el suyo; viene elegido el que pidió (group), el único que queda o el del empleado de la sesión
        $groups = $service->schedulable($process, $workstation) ? $service->scheduledNow($shift, $workstation, $production_order) : collect();
        $employee_id = $request->user()?->employee?->id;
        $chosen = $groups->firstWhere('id', $request->integer('group'))
            ?? ($groups->count() === 1 ? $groups->first() : $groups->first(fn ($row) => $employee_id && $row->employees->contains('id', $employee_id)));
        // Por unidades: el registro programado del lote en el puesto (sin él no se puede iniciar)
        $scheduled = $by_units ? $chosen : null;
        // Programado (también Corte, por lote): el grupo de trabajo son las personas que el líder programó
        $programmed = $chosen;
        // Cadena en secuencia: si el lote ya terminó en los puestos anteriores y a qué hora iniciaría
        // Puestos del lote que el mismo grupo inicia a la vez con este (PARCHE + RELOJERA): no se esperan entre sí
        $companions = $scheduled ? $service->companionsToStart($scheduled, $process) : collect();
        $sequence = $service->sequenceStatus($production_order, $workstation, $process, $companions->pluck('model'));
        // Por lote se trabaja una sola vez en el puesto: si el lote ya pasó por aquí, cuándo
        $registered = $service->measuredByUnits($workstation) ? null : $service->registeredDetail($production_order, $workstation);

        // Programado: quién del grupo está ocupado en otro trabajo en curso; a esos (y solo a esos) se les puede poner un
        // reemplazo libre del proceso, uno por uno
        $busy = $programmed ? $service->busyMap($workstation, $production_order->id) : collect();
        $group = $programmed ? $programmed->employees : collect();
        $replacements = $group->contains(fn ($employee) => $busy->has($employee->id))
            ? $service->eligibleEmployees($workstation, $production_order->id)
                ->map(fn ($employee) => array_merge($employee, ['workstation' => $workstation->name]))
                ->concat($service->borrowableEmployees($workstation, $process, $production_order->id)->reject(fn ($employee) => $employee['busy']))
                ->reject(fn ($employee) => $group->contains('id', $employee['id']))
                ->values()
            : collect();

        return array_merge($service->referenceData($production_order, $workstation, $process), [
            // Empleados que se pueden elegir: los de la programación o, sin programar, los asignados al puesto y libres
            'employees' => $programmed
                // Los prestados traen el puesto de donde vienen (lo eligió el líder al programar)
                ? $group->map(fn ($employee) => array_merge($service->employeePayload($employee), [
                    'borrowed_from' => json_decode($employee->pivot->settings ?? '[]', true)['borrowed_from'] ?? null,
                    'busy_in' => $busy->get($employee->id),
                ]))
                    ->values()
                : ($groups->count() > 1 ? [] : $service->eligibleEmployees($workstation, $production_order->id)),
            'scheduled' => $scheduled,
            // Varios grupos programados: para elegir el suyo (con sus personas, hora y meta) y cuál quedó elegido
            'groups' => $groups->count() > 1 ? $groups->map(fn ($row) => [
                'id' => $row->id,
                'start' => $service->scheduledStart($row)->format('g:i A'),
                'units' => $row->settings->scheduled->units ?? null,
                'employees' => $row->employees->map(fn ($employee) => $service->employeePayload($employee))->values(),
            ])->values() : [],
            'group' => $chosen?->id,
            // Programado: el grupo de trabajo lo define el líder en la programación; la estación no lo cambia
            'programmed' => (bool) $programmed || $groups->count() > 1,
            // El líder aún no programa el puesto: se puede iniciar cualquier referencia lista y el grupo lo elige el operario
            'free_start' => $service->schedulable($process, $workstation) && ! $service->workstationProgrammed($shift, $workstation),
            // Reemplazos posibles de los programados ocupados: libres del proceso, con su puesto
            'replacements' => $replacements,
            // Empleados del puesto ocupados en otro trabajo en curso (no se pueden elegir; la estación dice dónde están)
            'busy_employees' => $service->busyEmployees($workstation, $production_order->id),
            'multiplier' => $service->multiplierPayload($production_order, $workstation, $scheduled?->settings->multiplier ?? null),
            'siblings' => $scheduled ? $service->siblings($scheduled) : [],
            'companions' => $companions->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->model->name,
            ])->values(),
            // Empleados de otros puestos del mismo proceso padre para reforzar (los ocupados no se pueden elegir)
            'borrowable' => $service->borrowableEmployees($workstation, $process, $production_order->id),
            'creates_ingress' => $service->createsIngress($process, $workstation),
            'sequence' => $sequence,
            'measurement' => $service->measurementOf($workstation),
            'counts_at_finish' => $service->countsAtFinish($process, $workstation),
            'total_at_finish' => $service->totalAtFinish($process, $workstation),
            // ETIQUETADO: unidades que le faltan a la curva de la orden (para el tiempo estimado)
            'pending_units' => $service->countsAtFinish($process, $workstation)
                ? collect($service->pendingCurve($production_order, $workstation))->sum(fn ($destination) => collect($destination['sizes'])->sum('quantity'))
                : null,
            'counters' => $by_units ? $service->counters($production_order, $process, $workstation) : null,
            'registered' => $registered ? [
                'start' => $registered->start->format('Y-m-d H:i:s'),
                'end' => $registered->end?->format('Y-m-d H:i:s'),
            ] : null,
            // Por lote (Corte), la misma hora con que iniciaría: la programada o, adelantada, apenas quedó libre
            'start' => $sequence['ready']
                ? ($service->schedulable($process, $workstation) && $groups->isEmpty() ? now() : ($programmed && ! $by_units
                    ? $service->lotStart($service->startTime($shift, $workstation, $production_order), $programmed, $shift)
                    : $service->startTime($shift, $workstation, $production_order, $scheduled)))->format('Y-m-d H:i:s')
                : null,
        ]);
    }
}
