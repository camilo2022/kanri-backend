<?php

namespace App\Http\Requests\Productivity;

use App\Models\Productivity;

/**
 * Desactivar un turno: solo si todavía no ha iniciado (un turno en curso o terminado ya tiene trabajo registrado).
 */
class ProductivityDeleteRequest extends ProductivityFindRequest
{
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $shift = Productivity::find($this->input('id'));

            if ($shift->status() !== Productivity::PENDING) {
                $validator->errors()->add('id', $shift->status() === Productivity::IN_PROGRESS
                    ? 'El turno ya inició; no se puede desactivar.'
                    : 'El turno ya terminó; no se puede desactivar.');

                return;
            }

            if ($shift->movement_details()->exists()) {
                $validator->errors()->add('id', 'El turno tiene trabajo registrado; no se puede desactivar.');
            }
        });
    }
}
