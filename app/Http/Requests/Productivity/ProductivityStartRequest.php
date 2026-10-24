<?php

namespace App\Http\Requests\Productivity;

use App\Models\ProductionOrder;
use App\Services\StandardRuleService;
use App\Services\WorkLogService;

/**
 * Iniciar trabajo en el puesto con la orden escaneada: valida el puesto, el turno abierto y la orden (heredado de
 * ProductivityReferenceRequest) y además que el puesto no tenga un trabajo en curso, que sea el primer proceso
 * medido por lotes (por ahora solo Corte), que haya una regla estándar que aplique, que la orden tenga curva por
 * destino y que el lote no se haya registrado ya ni se esté trabajando en otro puesto.
 */
class ProductivityStartRequest extends ProductivityReferenceRequest
{
    protected $production_order = null;

    protected $rule = null;

    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = app(WorkLogService::class);

            if ($service->inProgress($this->shift(), $this->workstation())) {
                $validator->errors()->add('token', 'Este puesto ya tiene un trabajo en curso; finalízalo antes de iniciar otro.');

                return;
            }

            if (! $this->process()->isFirst() || ($this->process()->settings->productivity_measurement ?? null) !== 'lots') {
                $validator->errors()->add('token', 'Por ahora solo se registra trabajo en el primer proceso de la producción, medido por lotes (Corte).');

                return;
            }

            $this->production_order = ProductionOrder::where('uuid', $this->input('production_order'))->first();

            $this->rule = app(StandardRuleService::class)
                ->resolve($this->production_order, $this->workstation(), $this->process())['rule'] ?? null;

            if (! $this->rule) {
                $validator->errors()->add('production_order', 'No hay una regla estándar que aplique a esta referencia en este puesto.');

                return;
            }

            if (empty($service->destinations($this->production_order))) {
                $validator->errors()->add('production_order', 'La orden de producción no tiene unidades por destino y talla.');

                return;
            }

            if ($service->alreadyRegistered($this->production_order, $this->process())) {
                $validator->errors()->add('production_order', "El lote de esta orden ya se registró en {$this->process()->name}.");

                return;
            }

            if ($service->inProgressElsewhere($this->production_order, $this->process(), $this->workstation())) {
                $validator->errors()->add('production_order', 'Esta orden se está trabajando en este momento en otro puesto.');
            }
        });
    }

    /**
     * La orden llega en el cuerpo (no en la ruta, como al consultar la referencia).
     */
    protected function prepareForValidation()
    {
        $productionOrder = $this->input('production_order');

        parent::prepareForValidation();

        $this->merge(['production_order' => $productionOrder]);
    }

    public function productionOrder()
    {
        return $this->production_order;
    }

    public function rule()
    {
        return $this->rule;
    }
}
