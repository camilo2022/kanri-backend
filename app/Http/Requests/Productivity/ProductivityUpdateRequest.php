<?php

namespace App\Http\Requests\Productivity;

use App\Models\Productivity;

/**
 * Editar un turno. Valida lo mismo que crearlo (ProductivityStoreRequest: horario, meta por hora, paradas
 * programadas y que no se cruce con otro turno del proceso, sin contar este) y además, según su estado:
 * - Por iniciar: se puede cambiar todo.
 * - En curso: solo la hora de fin (lo demás se toma del turno guardado); no puede quedar antes de la hora actual
 *   y las paradas programadas deben seguir cabiendo.
 * - Terminado o desactivado: no se puede editar.
 * El proceso del turno no cambia.
 */
class ProductivityUpdateRequest extends ProductivityStoreRequest
{
    protected $shift = null;

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->shift) {
                $validator->errors()->add('id', 'El turno no existe o está desactivado.');

                return;
            }

            if ($this->shift->status() === Productivity::FINISHED) {
                $validator->errors()->add('id', 'El turno ya terminó; no se puede editar.');

                return;
            }

            // En curso: lo primero es que la hora de fin no quede en el pasado
            if ($this->shift->status() === Productivity::IN_PROGRESS
                && ! $validator->errors()->has('end_time')
                && $this->input('end_time') <= now()->format('H:i')
            ) {
                $validator->errors()->add('end_time', 'El turno está en curso: la hora de fin debe ser posterior a la hora actual.');
            }
        });

        parent::withValidator($validator);
    }

    public function shift()
    {
        return $this->shift;
    }

    protected function ignoredId(): ?int
    {
        return $this->shift?->id;
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'id' => 'Turno.',
        ]);
    }

    protected function prepareForValidation()
    {
        $this->shift = Productivity::find($this->route('id'));

        // El proceso no cambia (de un turno desactivado también se toma, para que solo se vea el error del turno)
        $stored = $this->shift ?? Productivity::onlyTrashed()->find($this->route('id'));

        if (! $stored) {
            return;
        }

        $this->merge([
            'model_type' => $stored->model_type,
            'model_id' => $stored->model_id,
        ]);

        if (! $this->shift) {
            return;
        }

        // En curso solo cambia la hora de fin: lo demás se toma del turno guardado
        if ($this->shift->status() === Productivity::IN_PROGRESS) {
            $settings = $this->shift->publicSettings();

            $this->merge([
                'date' => $this->shift->date->format('Y-m-d'),
                'start_time' => substr($this->shift->start_time, 0, 5),
                'settings' => [
                    'goal_hour' => $settings['goal_hour'],
                    'stops' => collect($settings['stops'])
                        ->map(fn ($stop) => ['stop_id' => $stop->stop_id, 'start_time' => $stop->start_time])
                        ->values()
                        ->all(),
                ],
            ]);
        }
    }
}
