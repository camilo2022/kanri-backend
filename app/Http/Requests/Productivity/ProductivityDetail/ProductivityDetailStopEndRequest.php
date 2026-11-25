<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Services\PermissionService;
use App\Services\ProductivityDetailService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Terminar la parada no programada en curso del puesto.
 */
class ProductivityDetailStopEndRequest extends FormRequest
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

            $open = app(ProductivityDetailService::class)->openStop($productivity_detail);

            if (! $open) {
                $validator->errors()->add('token', 'No hay una parada en curso.');
            } elseif ($open->settings->automatic ?? false) {
                $validator->errors()->add('token', sprintf('La parada %s se quita sola cuando haya unidades disponibles.', $open->settings->name ?? ''));
            } elseif (($open->settings->type ?? null) === 'programmed') {
                $validator->errors()->add('token', sprintf('La parada programada %s termina sola a las %s.', $open->settings->name ?? '', substr($open->settings->end, 11, 5)));
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
