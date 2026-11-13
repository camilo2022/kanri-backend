<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Registro programado de un turno (procesos por unidades), crudo, con las relaciones que traiga cargadas.
 */
class ProductivityDetailScheduledResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'productivity_detail' => $this->resource,
        ];
    }
}
