<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Puestos a los que entra el usuario al iniciar sesión, con el código que abre la estación (el mismo del QR). Recibe los
 * puestos que eligió ProductivityDetailService::employeeWorkstations.
 */
class ProductivityDetailWorkstationMineResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'workstations' => $this->resource->values(),
        ];
    }
}
