<?php

namespace App\Observers;

use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\MovementDetailQuantity;
use App\Services\MovementDetailService;

class MovementDetailObserver
{
    public function __construct(
        protected MovementDetailService $movement_detail_service
    ) {}

    /**
     * Handle the MovementDetail "created" event.
     */
    public function created(MovementDetail $movement_detail): void
    {
        //
    }

    /**
     * Handle the MovementDetail "updated" event.
     */
    public function updated(MovementDetail $movement_detail): void
    {
        if (! $movement_detail->wasChanged('status')) {
            return;
        }

        $this->handleMovementStatus($movement_detail);

        // Reconstruir las curvas tras el cambio de estado.
        $this->movement_detail_service->reconstruct($movement_detail);
    }

    /**
     * Handle the MovementDetail "deleted" event.
     */
    public function deleted(MovementDetail $movement_detail): void
    {
        //
    }

    /**
     * Handle the MovementDetail "restored" event.
     */
    public function restored(MovementDetail $movement_detail): void
    {
        //
    }

    /**
     * Handle the MovementDetail "force deleted" event.
     */
    public function forceDeleted(MovementDetail $movement_detail): void
    {
        //
    }

    protected function handleMovementStatus(MovementDetail $movement_detail): void
    {
        if ($movement_detail->model_type !== Movement::class) {
            return;
        }

        $origin_movement = Movement::find($movement_detail->model_id);

        if (! $origin_movement) {
            return;
        }

        $total_input = MovementDetailQuantity::whereHas('movement_detail', function ($query) use ($origin_movement) {
            $query->where('movement_id', $origin_movement->id)
                ->where('status', MovementDetail::APPROVED);
        })->sum('quantity');

        $total_output = MovementDetailQuantity::whereHas('movement_detail', function ($query) use ($origin_movement) {
            $query->where('model_type', Movement::class)
                ->where('model_id', $origin_movement->id)
                ->whereIn('status', [MovementDetail::APPROVED, MovementDetail::PENDING]);
        })->sum('quantity');

        $remaining_quantity = (int) $total_input - (int) $total_output;

        logger([$total_input, $total_output]);

        if ($remaining_quantity === 0) {
            $origin_movement->update([
                'status' => 'Salida',
            ]);
        } elseif ($remaining_quantity > 0 && $remaining_quantity < $total_input) {
            $origin_movement->update([
                'status' => 'Movimiento',
            ]);
        } else {
            $origin_movement->update([
                'status' => 'Ingreso',
            ]);
        }
    }
}
