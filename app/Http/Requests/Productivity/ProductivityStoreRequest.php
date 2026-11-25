<?php

namespace App\Http\Requests\Productivity;

use App\Models\Productivity;
use App\Models\Process;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Crear un turno: horario, meta por hora (obligatoria si el proceso se mide por lotes) y que no se cruce con otro turno
 * del proceso.
 */
class ProductivityStoreRequest extends FormRequest
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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['productivity.store'];
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
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->date('date')->lt(today())) {
                $validator->errors()->add('date', 'No se pueden crear turnos en fechas anteriores a hoy.');

                return;
            }

            if (Productivity::where('model_type', $this->input('model_type'))
                ->where('model_id', $this->input('model_id'))
                ->whereDate('date', $this->input('date'))
                ->where('start_time', '<', $this->input('end_time'))
                ->where('end_time', '>', $this->input('start_time'))
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
            'model_type' => 'Tipo de elemento.',
            'model_id' => 'Identificador del elemento.',
            'date' => 'Fecha del turno.',
            'goal' => 'Meta por hora.',
            'start_time' => 'Hora de inicio.',
            'end_time' => 'Hora de fin.',
        ];
    }
}
