<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Services\ProductivityDetailService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * −1 en el trabajo en curso (por si se tocó de más): solo quita unidades que haya sumado este mismo registro.
 */
class ProductivityDetailMinusRequest extends FormRequest
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
            $process = $context['process'];

            $service = app(ProductivityDetailService::class);

            $productivity_detail = $service->inProgress($workstation, $this->integer('productivity_detail_id'));

            if (! $productivity_detail) {
                $validator->errors()->add('token', 'Este puesto no tiene un trabajo en curso.');

                return;
            }

            if (! $service->countsWithPlus($process, $workstation)) {
                $validator->errors()->add('token', $service->measuredByUnits($workstation)
                    ? "{$workstation->name} registra sus unidades al finalizar."
                    : "{$workstation->name} se mide por lote: no lleva conteo de unidades.");

                return;
            }

            if ($service->producedBy($productivity_detail) < 1
                && $service->companions($productivity_detail)->every(fn ($row) => $service->producedBy($row) < 1)) {
                $validator->errors()->add('token', 'Este trabajo no tiene unidades para quitar.');
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
