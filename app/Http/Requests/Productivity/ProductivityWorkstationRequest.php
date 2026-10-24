<?php

namespace App\Http\Requests\Productivity;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Productivity;
use App\Models\Subprocess;
use App\Services\WorkLogService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductivityWorkstationRequest extends FormRequest
{
    protected $workstation = null;

    protected $process = null;

    protected $shift = null;

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
            'token' => ['required', 'string', 'uuid'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // El puesto de trabajo es el proceso, subproceso u operación activo que tiene ese código
            foreach ([Operation::class, Subprocess::class, Process::class] as $model) {
                $this->workstation = $model::where('settings->uuid', $this->input('token'))->first();

                if ($this->workstation) {
                    break;
                }
            }

            if (! $this->workstation) {
                $validator->errors()->add('token', 'El código QR no corresponde a ningún puesto de trabajo activo.');

                return;
            }

            if ($this->workstation instanceof Process && $this->workstation->subprocesses()->exists()) {
                $validator->errors()->add('token', 'El proceso tiene subprocesos; escanea el QR del subproceso.');

                return;
            }

            if ($this->workstation instanceof Subprocess && $this->workstation->operations()->exists()) {
                $validator->errors()->add('token', 'El subproceso tiene operaciones; escanea el QR de la operación.');

                return;
            }

            $this->process = match (true) {
                $this->workstation instanceof Operation => $this->workstation->subprocess()->first()?->process()->first(),
                $this->workstation instanceof Subprocess => $this->workstation->process()->first(),
                default => $this->workstation,
            };

            if (! $this->process) {
                $validator->errors()->add('token', 'El puesto de trabajo no pertenece a ningún proceso activo.');

                return;
            }

            // Turno: el que tiene un trabajo en curso de este puesto (aunque ya haya pasado su hora de fin, para
            // poder terminarlo) o, si no, el abierto: el de hoy del proceso cuya franja incluye la hora actual
            $now = now()->format('H:i:s');

            $this->shift = Productivity::where('model_type', Process::class)
                ->where('model_id', $this->process->id)
                ->whereDate('date', '>=', today()->subDay())
                ->whereNotNull('settings->work_in_progress->'.WorkLogService::key($this->workstation))
                ->latest('date')
                ->first()
                ?? Productivity::where('model_type', Process::class)
                    ->where('model_id', $this->process->id)
                    ->whereDate('date', today())
                    ->where('start_time', '<=', $now)
                    ->where('end_time', '>', $now)
                    ->first();

            if (! $this->shift) {
                $validator->errors()->add('token', "No hay un turno abierto en este momento para el proceso {$this->process->name}.");
            }
        });
    }

    public function workstation()
    {
        return $this->workstation;
    }

    public function process()
    {
        return $this->process;
    }

    public function shift()
    {
        return $this->shift;
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'uuid' => 'El código QR no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'Código QR del puesto de trabajo',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['token' => $this->route('token')]);
    }
}
