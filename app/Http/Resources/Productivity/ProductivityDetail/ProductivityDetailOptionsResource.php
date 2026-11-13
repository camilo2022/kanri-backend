<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use App\Services\ProductivityDetailService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Opciones para programar un turno (procesos por unidades): los empleados ocupados en otros turnos. Los puestos los arma
 * el frontend con el model del turno (productivity/find) y los lotes, con su estimado por puesto, los lista
 * production_orders/all con process_id, model_type y model_id. Recibe el turno.
 */
class ProductivityDetailOptionsResource extends JsonResource
{
    public function toArray($request)
    {
        $service = app(ProductivityDetailService::class);
        // Trabajos de otros turnos (de cualquier proceso) que se cruzan con este: sus empleados están ocupados ahí
        $busy = $service->overlappingRows($this->startsAt(), $this->endsAt())
            ->where('productivity_id', '!=', $this->id)
            ->map(fn ($row) => [
                'employees' => $row->employees->pluck('id')->values(),
                'name' => ($row->model?->name ?? 'otro puesto').' ('.($row->productivity?->model?->name ?? 'otro turno').')',
                'start' => $row->start->format('Y-m-d H:i:s'),
                'end' => $service->busyUntil($row)->format('Y-m-d H:i:s'),
            ])
            ->values();

        return [
            'busy' => $busy,
            // Primer proceso (Corte): se programa por lote, con la hora de fin que digita quien programa
            'by_lot' => $this->model->isFirst(),
            // Corte en secuencia: se programan las referencias y su orden; las horas las calcula el sistema
            'chain' => $service->programsChain($this->model),
            // Puestos en el orden de la cadena con sus empleados (los asignados trabajan por defecto)
            'workstations' => $service->programsChain($this->model) ? $service->workstations($this->model) : [],
            // Puestos que se pueden trabajar a la vez (niveles en paralelo): el mismo grupo puede estar en dos del mismo lote
            'parallel_groups' => $service->parallelGroups($this->model),
        ];
    }
}
