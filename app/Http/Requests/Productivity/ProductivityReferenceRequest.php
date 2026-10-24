<?php

namespace App\Http\Requests\Productivity;

/**
 * Escaneo de la ficha técnica en el puesto de trabajo: valida el puesto (uuid de su QR, nivel más bajo y turno
 * abierto, heredado de ProductivityWorkstationRequest) y la orden de producción escaneada (su uuid).
 */
class ProductivityReferenceRequest extends ProductivityWorkstationRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'production_order' => ['required', 'string', 'uuid', 'exists:production_orders,uuid,deleted_at,NULL'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'production_order.uuid' => 'El código escaneado no es de una orden de producción.',
            'production_order.exists' => 'La orden de producción no existe o está desactivada.',
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'production_order' => 'Orden de producción escaneada',
        ]);
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $this->merge(['production_order' => $this->route('uuid')]);
    }
}
