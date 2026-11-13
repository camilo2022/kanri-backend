<?php

namespace App\Http\Requests\Productivity;

use App\Models\Productivity;
use App\Models\Process;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Editar un turno. Valida lo mismo que crearlo (horario, meta por hora y que no se cruce con otro turno del proceso, sin
 * contar este) y además, según su estado:
 * - Por iniciar: se puede cambiar todo.
 * - En curso: solo la hora de fin (lo demás se toma del turno guardado), que no puede quedar antes de la hora actual.
 * - Terminado o desactivado: no se puede editar.
 * El proceso del turno no cambia.
 */
class ProductivityUpdateRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'model_type' => ['required', 'string', 'in:'.Process::class],
            'model_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'settings' => ['required', 'array'],
            'settings.goal_hour' => ['nullable', 'numeric', 'min:1'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $shift = Productivity::find($this->route('id'));

            if (! $shift) {
                $validator->errors()->add('id', 'El turno no existe o está desactivado.');

                return;
            }

            if ($shift->status === Productivity::FINISHED) {
                $validator->errors()->add('id', 'El turno ya terminó; no se puede editar.');

                return;
            }

            // En curso: lo primero es que la hora de fin no quede en el pasado
            if ($shift->status === Productivity::IN_PROGRESS
                && ! $validator->errors()->has('end_time')
                && $this->input('end_time') <= now()->format('H:i')
            ) {
                $validator->errors()->add('end_time', 'El turno está en curso: la hora de fin debe ser posterior a la hora actual.');
            }

            if (! $validator->errors()->has('date') && $this->date('date')->lt(today())) {
                $validator->errors()->add('date', 'No se pueden mover turnos a fechas anteriores a hoy.');
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (Productivity::where('model_type', $this->input('model_type'))
                ->where('model_id', $this->input('model_id'))
                ->whereDate('date', $this->input('date'))
                ->where('start_time', '<', $this->input('end_time'))
                ->where('end_time', '>', $this->input('start_time'))
                ->where('id', '!=', $shift->id)
                ->exists()
            ) {
                $validator->errors()->add('start_time', 'Ya existe un turno para este elemento que se cruza con este horario.');
            }

            if (! $this->filled('settings.goal_hour') && (Process::find($this->input('model_id'))?->settings->productivity_measurement ?? null) === 'lots') {
                $validator->errors()->add('settings.goal_hour', 'Es obligatorio cuando el proceso se mide por lotes.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'id' => 'Turno.',
            'model_type' => 'Tipo de elemento.',
            'model_id' => 'Identificador del elemento.',
            'date' => 'Fecha del turno.',
            'goal' => 'Meta por hora.',
            'start_time' => 'Hora de inicio.',
            'end_time' => 'Hora de fin.',
        ];
    }

    protected function prepareForValidation()
    {
        $shift = Productivity::find($this->route('id'));

        // El proceso no cambia (de un turno desactivado también se toma, para que solo se vea el error del turno)
        $stored = $shift ?? Productivity::onlyTrashed()->find($this->route('id'));

        if (! $stored) {
            return;
        }

        $this->merge([
            'model_type' => $stored->model_type,
            'model_id' => $stored->model_id,
        ]);

        if (! $shift) {
            return;
        }

        // En curso solo cambia la hora de fin: lo demás se toma del turno guardado
        if ($shift->status === Productivity::IN_PROGRESS) {
            $this->merge([
                'date' => $shift->date->format('Y-m-d'),
                'start_time' => substr($shift->start_time, 0, 5),
                'settings' => $shift->publicSettings(),
            ]);
        }
    }
}
