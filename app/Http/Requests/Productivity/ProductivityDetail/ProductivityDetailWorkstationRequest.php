<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class ProductivityDetailWorkstationRequest extends FormRequest
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
        return ['productivity.workstation'];
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'uuid'],
            'productivity_detail_id' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $workstation = null;

            foreach ([Operation::class, Subprocess::class, Process::class] as $model) {
                $workstation ??= $model::where('settings->uuid', $this->input('token'))->first();
            }

            if (! $workstation) {
                $validator->errors()->add('token', 'El código QR no corresponde a ningún puesto de trabajo activo.');

                return;
            }

            // Solo se trabaja en el último nivel: proceso sin subprocesos o subproceso sin operaciones
            if ($workstation instanceof Process && $workstation->subprocesses()->exists()) {
                $validator->errors()->add('token', 'El proceso tiene subprocesos; escanea el QR del subproceso.');

                return;
            }

            if ($workstation instanceof Subprocess && $workstation->operations()->exists()) {
                $validator->errors()->add('token', 'El subproceso tiene operaciones; escanea el QR de la operación.');

                return;
            }

            $process = match (true) {
                $workstation instanceof Operation => $workstation->subprocess()->first()?->process()->first(),
                $workstation instanceof Subprocess => $workstation->process()->first(),
                default => $workstation,
            };

            if (! $process) {
                $validator->errors()->add('token', 'El puesto de trabajo no pertenece a ningún proceso activo.');
            }
        });
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
