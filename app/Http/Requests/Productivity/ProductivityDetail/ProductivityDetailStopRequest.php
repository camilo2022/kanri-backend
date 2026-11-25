<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Stop;
use App\Services\PermissionService;
use App\Services\ProductivityDetailService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Registrar una parada en el trabajo en curso del puesto: programada (termina sola con la duración del catálogo) o no
 * programada (hasta «Terminar parada»). Una a la vez y solo mientras el turno no haya terminado.
 */
class ProductivityDetailStopRequest extends FormRequest
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
            'stop_id' => ['required', 'integer', 'exists:subitems,id,item_id,'.Stop::ITEM_ID.',deleted_at,NULL'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $context = app(ProductivityDetailService::class)->workstationContext((string) $this->input('token'));

            if ($context['error']) {
                $validator->errors()->add('token', $context['error']);

                return;
            }

            $workstation = $context['workstation'];

            $service = app(ProductivityDetailService::class);

            $productivity_detail = $service->inProgress($workstation, $this->integer('productivity_detail_id'));

            if (! $productivity_detail) {
                $validator->errors()->add('token', 'Este puesto no tiene un trabajo en curso.');

                return;
            }

            $stop = Stop::with('stop_type')->find($this->input('stop_id'));

            if ($productivity_detail->productivity->endsAt()->lte(now())) {
                $validator->errors()->add('stop_id', 'El turno ya terminó; solo se puede finalizar el trabajo.');

                return;
            }

            // La espera de unidades solo la abre y la cierra el sistema
            if ($stop->settings->waiting_units ?? false) {
                $validator->errors()->add('stop_id', 'La parada de espera de unidades la registra el sistema; no se puede elegir a mano.');

                return;
            }

            if ($service->isProgrammed($stop) && (int) ($stop->settings->range_time ?? 0) < 1) {
                $validator->errors()->add('stop_id', 'La parada programada no tiene duración en el catálogo.');

                return;
            }

            // La espera de unidades la cierra el sistema: no impide registrar otra parada
            if (($open = app(ProductivityDetailService::class)->openStop($productivity_detail)) && ! ($open->settings->automatic ?? false)) {
                $validator->errors()->add('stop_id', ($open->settings->type ?? null) === 'programmed'
                    ? sprintf('La parada %s está en curso hasta las %s.', $open->settings->name ?? '', substr($open->settings->end, 11, 5))
                    : 'Ya hay una parada en curso; termínala antes de registrar otra.');

                return;
            }

            // La programada no puede terminar después del fin del turno
            $minutes = (int) ($stop->settings->range_time ?? 0);
            $shift_end = $productivity_detail->productivity->endsAt();

            if ($service->isProgrammed($stop) && now()->addMinutes($minutes)->gt($shift_end)) {
                $validator->errors()->add('stop_id', sprintf(
                    'La parada %s dura %d min y terminaría a las %s, después del fin del turno (%s). No se puede registrar.',
                    $stop->name,
                    $minutes,
                    now()->addMinutes($minutes)->format('g:i A'),
                    $shift_end->format('g:i A')
                ));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'token' => 'Código QR del puesto de trabajo',
            'stop_id' => 'Parada',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['token' => $this->route('token')]);
    }
}
