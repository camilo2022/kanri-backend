<?php

namespace App\Http\Requests\Productivity;

use App\Models\Stop;
use App\Services\WorkLogService;

/**
 * Iniciar una parada no programada (paro mecánico, falta de insumos…) en el trabajo en curso del puesto.
 * Solo paradas de tipo no programada, una a la vez y nunca dentro de una parada programada del turno.
 */
class ProductivityStopRequest extends ProductivityWorkLogRequest
{
    protected $stop = null;

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'stop_id' => ['required', 'integer', 'exists:subitems,id,item_id,'.Stop::ITEM_ID.',deleted_at,NULL'],
        ]);
    }

    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->stop = Stop::with('stop_type')->find($this->input('stop_id'));

            if ($this->stop->stop_type->first()?->settings->has_defined_time_range ?? false) {
                $validator->errors()->add('stop_id', 'Las paradas programadas se definen en el turno.');

                return;
            }

            if ($this->openStop()) {
                $validator->errors()->add('stop_id', 'Ya hay una parada en curso; termínala antes de registrar otra.');

                return;
            }

            $programmed = app(WorkLogService::class)->programmedStopAt($this->shift(), now());

            if ($programmed) {
                $validator->errors()->add('stop_id', sprintf(
                    'En este momento es la parada programada %s (%s a %s); no se puede registrar otra parada encima.',
                    $programmed['name'],
                    substr($programmed['start'], 11, 5),
                    substr($programmed['end'], 11, 5)
                ));
            }
        });
    }

    public function stop()
    {
        return $this->stop;
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'integer' => 'Debe ser un número entero.',
            'exists' => 'No hay ningún registro.',
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'stop_id' => 'Parada',
        ]);
    }
}
