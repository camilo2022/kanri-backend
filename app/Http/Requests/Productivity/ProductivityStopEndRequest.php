<?php

namespace App\Http\Requests\Productivity;

/**
 * Terminar la parada no programada en curso del puesto.
 */
class ProductivityStopEndRequest extends ProductivityWorkLogRequest
{
    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->openStop()) {
                $validator->errors()->add('token', 'No hay una parada en curso.');
            }
        });
    }
}
