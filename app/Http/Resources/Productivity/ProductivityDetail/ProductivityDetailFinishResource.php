<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resumen del registro finalizado (lo que devuelve ProductivityDetailService::finish): tiempos, personas, paradas,
 * eficiencia, unidades y los ingresos creados.
 */
class ProductivityDetailFinishResource extends JsonResource
{
    public function toArray($request)
    {
        return $this->resource;
    }
}
