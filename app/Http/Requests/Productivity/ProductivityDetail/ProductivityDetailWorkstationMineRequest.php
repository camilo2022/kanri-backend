<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Puestos a los que entra el usuario que inició sesión. No recibe datos: salen de su empleado.
 */
class ProductivityDetailWorkstationMineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
