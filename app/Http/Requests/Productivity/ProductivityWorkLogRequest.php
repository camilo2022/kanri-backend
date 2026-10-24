<?php

namespace App\Http\Requests\Productivity;

use App\Services\WorkLogService;

/**
 * Acciones sobre el trabajo en curso del puesto (paradas y finalizar): valida el puesto y su turno (heredado de
 * ProductivityWorkstationRequest) y que el puesto tenga un trabajo en curso.
 */
class ProductivityWorkLogRequest extends ProductivityWorkstationRequest
{
    protected $work = null;

    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->work = app(WorkLogService::class)->inProgress($this->shift(), $this->workstation());

            if (! $this->work) {
                $validator->errors()->add('token', 'Este puesto no tiene un trabajo en curso.');
            }
        });
    }

    public function work()
    {
        return $this->work;
    }

    /**
     * Parada no programada abierta (sin hora de fin) del trabajo en curso, o null.
     */
    public function openStop(): ?array
    {
        return collect($this->work['stops'] ?? [])->first(fn ($stop) => empty($stop['end']));
    }
}
