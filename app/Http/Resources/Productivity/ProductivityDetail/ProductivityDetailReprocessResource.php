<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use App\Services\ProductivityDetailService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reprocesos de un registro de trabajo: los registrados y los pasos del lote a los que se pueden devolver unidades
 * (con su etapa en la línea y lo que llevan hecho).
 */
class ProductivityDetailReprocessResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'productivity_detail' => [
                'id' => $this->id,
                'model' => $this->model,
                'production_order' => $this->production_order,
                'reprocesses' => $this->settings->reprocesses ?? [],
            ],
            'steps' => app(ProductivityDetailService::class)->reprocessSteps($this->production_order, $this->productivity->model),
        ];
    }
}
