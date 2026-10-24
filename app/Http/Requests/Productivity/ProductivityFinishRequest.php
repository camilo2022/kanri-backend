<?php

namespace App\Http\Requests\Productivity;

use App\Models\ProductionOrder;
use App\Services\WorkLogService;

/**
 * Finalizar el trabajo en curso con las unidades por destino y talla (en Corte: lo que se cortó del lote).
 * Cada destino y talla debe ser de la orden que se está trabajando y debe haber al menos una unidad.
 */
class ProductivityFinishRequest extends ProductivityWorkLogRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*.production_order_detail_id' => ['required', 'integer'],
            'quantities.*.size_id' => ['required', 'integer'],
            'quantities.*.quantity' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
    }

    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $productionOrder = ProductionOrder::find($this->work()['production_order_id']);

            // Tallas permitidas por destino de la orden: [production_order_detail_id => [size_id, …]]
            $allowed = collect(app(WorkLogService::class)->destinations($productionOrder))
                ->mapWithKeys(fn ($destination) => [
                    $destination['production_order_detail_id'] => collect($destination['sizes'])->pluck('size_id')->all(),
                ]);

            foreach ($this->input('quantities') as $i => $quantity) {
                if (! in_array((int) $quantity['size_id'], $allowed->get((int) $quantity['production_order_detail_id'], []), true)) {
                    $validator->errors()->add("quantities.$i.size_id", 'El destino o la talla no son de la orden que se está trabajando.');
                }
            }

            if (collect($this->input('quantities'))->sum('quantity') < 1) {
                $validator->errors()->add('quantities', 'Registra al menos una unidad.');
            }
        });
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'array' => 'Debe ser una lista.',
            'integer' => 'Debe ser un número entero.',
            'min' => 'Debe ser mayor o igual a :min.',
            'max' => 'Debe ser menor o igual a :max.',
            'quantities.min' => 'Registra al menos un destino y talla.',
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'quantities' => 'Unidades por destino y talla',
            'quantities.*.production_order_detail_id' => 'Destino',
            'quantities.*.size_id' => 'Talla',
            'quantities.*.quantity' => 'Unidades',
        ]);
    }
}
