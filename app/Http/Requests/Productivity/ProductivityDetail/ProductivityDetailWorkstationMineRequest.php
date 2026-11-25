<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Services\PermissionService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Puestos a los que entra el usuario que inició sesión. No recibe datos: salen de su empleado.
 */
class ProductivityDetailWorkstationMineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['productivity.workstation'];
    }

    public function rules(): array
    {
        return [];
    }
}
