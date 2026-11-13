<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Puesto de trabajo escaneado, crudo, con sus padres (subproceso y proceso) y los turnos del proceso; su registro en
 * curso, si lo tiene o se pidió uno; y, en los puestos programados, la lista de registros en curso (uno por grupo), para
 * que cada grupo elija el suyo. La pantalla decide con qué turno se trabaja.
 */
class ProductivityDetailWorkstationResource extends JsonResource
{
    public function toArray($request)
    {
        $productivity_detail = $this->resource['productivity_detail'];

        return [
            'workstation' => $this->resource['workstation'],
            'productivity_detail' => $productivity_detail ? (new ProductivityDetailResource($productivity_detail))->toArray($request)['productivity_detail'] : null,
            'productivity_details' => $this->resource['productivity_details']
                ->map(fn ($row) => (new ProductivityDetailResource($row))->toArray($request)['productivity_detail'])
                ->values(),
            'references' => $this->resource['references'],
        ];
    }
}
