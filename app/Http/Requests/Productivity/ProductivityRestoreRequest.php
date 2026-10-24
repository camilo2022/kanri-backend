<?php

namespace App\Http\Requests\Productivity;

use App\Models\Productivity;

/**
 * Restaurar (activar) un turno desactivado: no debe cruzarse con otro turno activo del mismo proceso.
 */
class ProductivityRestoreRequest extends ProductivityFindRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:productivity,id,deleted_at,NOT_NULL'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'exists' => 'El turno no existe o ya está activo.',
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $shift = Productivity::onlyTrashed()->find($this->input('id'));

            if (Productivity::where('model_type', $shift->model_type)
                ->where('model_id', $shift->model_id)
                ->whereDate('date', $shift->date)
                ->where('start_time', '<', $shift->end_time)
                ->where('end_time', '>', $shift->start_time)
                ->exists()
            ) {
                $validator->errors()->add('id', 'Ya hay otro turno activo que se cruza con este horario; ajústalo antes de activar este.');
            }
        });
    }
}
