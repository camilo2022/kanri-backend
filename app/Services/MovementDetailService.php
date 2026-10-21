<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\ProductionOrderDetail;
use Illuminate\Database\Eloquent\Collection;

class MovementDetailService
{
    public function reconstruct(MovementDetail $movement_detail): void
    {
        $processed = [];

        // 1. Production order detail del modelo (solo si el modelo es un Movement).
        if ($movement_detail->model_type === Movement::class) {
            $model_pod = $movement_detail->model?->production_order_detail;

            if ($model_pod) {
                $this->reconstruct_production_order_detail($model_pod);
                $processed[] = $model_pod->id;
            }
        }

        // 2. Production order detail del movimiento del detalle.
        $movement_pod = $movement_detail->movement?->production_order_detail;

        if ($movement_pod && !in_array($movement_pod->id, $processed, true)) {
            $this->reconstruct_production_order_detail($movement_pod, false);
        }
    }

    private function reconstruct_production_order_detail(ProductionOrderDetail $production_order_detail, bool $apply_redistributions = true): void
    {
        $movements = Movement::with([
            'model',
            'movement_details_input' => [
                'movement_detail_redistributions',
                'movement_detail_quantities',
            ],
            'movement_details_output' => [
                'movement_detail_redistributions',
                'movement_detail_quantities',
            ],
        ])
            ->where('production_order_detail_id', $production_order_detail->id)
            ->get();

        // Todas las tallas parten en 0 para que no queden valores viejos.
        $accum = $production_order_detail
            ->production_order_detail_quantities()
            ->pluck('size_id')
            ->mapWithKeys(fn ($size_id) => [$size_id => 0])
            ->all();

        // IDs de detalles cuyas redistribuciones ya se aplicaron
        // (evita contarlas dos veces si el detalle aparece como entrada y salida).
        $redistributed = [];

        foreach ($movements as $movement) {

            // Sumar entradas aprobadas.
            $inputs = $movement->movement_details_input
                ->where('status', MovementDetail::APPROVED);

            foreach ($inputs as $input) {

                foreach ($input->movement_detail_quantities as $quantity) {
                    $this->accumulate($accum, $quantity->size_id, $quantity->quantity);
                }

                if ($apply_redistributions && !isset($redistributed[$input->id])) {
                    $this->apply_redistributions($accum, $input->movement_detail_redistributions);
                    $redistributed[$input->id] = true;
                }
            }

            // Restar salidas aprobadas o pendientes.
            $outputs = $movement->movement_details_output
                ->whereIn('status', [
                    MovementDetail::APPROVED,
                    MovementDetail::PENDING,
                ]);

            foreach ($outputs as $output) {

                foreach ($output->movement_detail_quantities as $quantity) {
                    $this->accumulate($accum, $quantity->size_id, -$quantity->quantity);
                }

                if ($apply_redistributions && !isset($redistributed[$output->id])) {
                    $this->apply_redistributions($accum, $output->movement_detail_redistributions);
                    $redistributed[$output->id] = true;
                }
            }
        }

        // Actualizar las cantidades reconstruidas.
        foreach ($accum as $size_id => $quantity) {
            $production_order_detail
                ->production_order_detail_quantities()
                ->where('size_id', $size_id)
                ->update([
                    'quantity' => $quantity,
                ]);
        }
    }

    private function accumulate(array &$accum, int $size_id, int $quantity): void
    {
        $accum[$size_id] = ($accum[$size_id] ?? 0) + $quantity;
    }

    private function apply_redistributions(array &$accum, Collection $redistributions): void
    {
        foreach ($redistributions as $redistribution) {

            // Restar de la talla de salida.
            $this->accumulate($accum, $redistribution->output_size_id, -$redistribution->quantity);

            // Sumar a la talla de entrada.
            $this->accumulate($accum, $redistribution->input_size_id, $redistribution->quantity);
        }
    }
}
