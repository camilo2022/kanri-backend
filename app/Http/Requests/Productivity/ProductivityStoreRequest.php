<?php

namespace App\Http\Requests\Productivity;

use App\Models\Process;
use App\Models\Productivity;
use App\Models\Stop;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductivityStoreRequest extends FormRequest
{
    protected array $stops = [];

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
            'settings.stops' => ['nullable', 'array'],
            // distinct: la parada se relaciona con el turno en model_has_subitems, que no admite repetidas
            'settings.stops.*.stop_id' => ['required', 'integer', 'distinct', 'exists:subitems,id,item_id,'.Stop::ITEM_ID.',deleted_at,NULL'],
            'settings.stops.*.start_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (Productivity::where('model_type', $this->input('model_type'))
                ->where('model_id', $this->input('model_id'))
                ->whereDate('date', $this->input('date'))
                ->where('start_time', '<', $this->input('end_time'))
                ->where('end_time', '>', $this->input('start_time'))
                ->when($this->ignoredId(), fn ($query, $id) => $query->where('id', '!=', $id))
                ->exists()
            ) {
                $validator->errors()->add('start_time', 'Ya existe un turno para este elemento que se cruza con este horario.');
            }

            if (! $this->filled('settings.goal_hour') && (Process::find($this->input('model_id'))?->settings->productivity_measurement ?? null) === 'lots') {
                $validator->errors()->add('settings.goal_hour', 'Es obligatorio cuando el proceso se mide por lotes.');
            }

            $this->validateStops($validator);
        });
    }

    /**
     * Turno que no cuenta al buscar cruces de horario (al editar, el mismo turno).
     */
    protected function ignoredId(): ?int
    {
        return null;
    }

    /**
     * Paradas programadas del turno (almuerzo, desayuno…): solo de tipo programada, con su fin calculado con la
     * duración del catálogo (settings.range_time, en minutos), dentro del turno y sin cruzarse entre ellas.
     */
    protected function validateStops($validator): void
    {
        $stops = [];

        foreach ($this->input('settings.stops', []) as $i => $item) {
            $stop = Stop::with('stop_type')->find($item['stop_id']);
            $minutes = (int) ($stop?->settings->range_time ?? 0);

            if (! ($stop?->stop_type->first()?->settings->has_defined_time_range ?? false)) {
                $validator->errors()->add("settings.stops.$i.stop_id", 'Solo se pueden programar paradas de tipo programada.');

                continue;
            }

            if ($minutes < 1) {
                $validator->errors()->add("settings.stops.$i.stop_id", 'La parada no tiene duración en el catálogo.');

                continue;
            }

            $start = Carbon::createFromFormat('H:i', $item['start_time']);
            $end = $start->copy()->addMinutes($minutes);

            if ($start->format('H:i') < $this->input('start_time') || ! $end->isSameDay($start) || $end->format('H:i') > $this->input('end_time')) {
                $validator->errors()->add("settings.stops.$i.start_time", 'La parada debe quedar dentro del horario del turno.');

                continue;
            }

            foreach ($stops as $other) {
                if ($start->format('H:i') < $other['end_time'] && $end->format('H:i') > $other['start_time']) {
                    $validator->errors()->add("settings.stops.$i.start_time", 'Se cruza con otra parada programada.');

                    continue 2;
                }
            }

            $stops[] = [
                'stop_id' => $stop->id,
                'start_time' => $start->format('H:i'),
                'end_time' => $end->format('H:i'),
            ];
        }

        $this->stops = collect($stops)->sortBy('start_time')->values()->all();
    }

    /**
     * Settings del turno con las paradas programadas ya validadas y con su hora de fin.
     */
    public function settings(): array
    {
        return array_merge($this->input('settings', []), ['stops' => $this->stops]);
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'date' => 'Debe ser una fecha válida.',
            'date_format' => 'Debe tener el formato HH:MM.',
            'after' => 'Debe ser posterior a la hora de inicio.',
            'min' => 'Debe ser mayor o igual a :min.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'exists' => 'No hay ningún registro.',
            'distinct' => 'La parada está repetida en el turno.',
        ];
    }

    public function attributes(): array
    {
        return [
            'model_type' => 'Tipo de elemento.',
            'model_id' => 'Identificador del elemento.',
            'date' => 'Fecha del turno.',
            'goal' => 'Meta por hora.',
            'start_time' => 'Hora de inicio.',
            'end_time' => 'Hora de fin.',
            'settings.stops' => 'Paradas programadas.',
            'settings.stops.*.stop_id' => 'Parada.',
            'settings.stops.*.start_time' => 'Hora de inicio de la parada.',
        ];
    }
}
