<?php

namespace App\Observers;

use App\Models\MovementDetail;
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

        $this->movement_detail_service->refreshOriginStatus($movement_detail);

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
}
