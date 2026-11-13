<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\ProductivityDetail;
use App\Services\ProductivityDetailService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Registrar un reproceso desde un trabajo iniciado (revisión, secado…): N unidades del lote vuelven a los pasos
 * elegidos, que las hacen otra vez. Lo decide quien tenga el permiso; el sistema no impone a dónde vuelven.
 */
class ProductivityDetailReprocessRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:productivity_details,id'],
            'units' => ['required', 'integer', 'min:1', 'max:100000'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*' => ['required', 'string', 'distinct'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $detail = ProductivityDetail::with(['productivity' => ['model'], 'production_order'])->find($this->integer('id'));

            // Solo en procesos que permiten reprocesos (settings.reprocesses, Lavandería)
            if (! ($detail->productivity?->model?->settings->reprocesses ?? false)) {
                $validator->errors()->add('id', ($detail->productivity?->model?->name ?? 'El proceso').' no permite reprocesos.');

                return;
            }

            if (! isset($detail->settings->scanned_at)) {
                $validator->errors()->add('id', 'El trabajo aún no se ha iniciado: no tiene unidades para reprocesar.');

                return;
            }

            // Solo pasos que el lote lleva en el proceso y que ya hicieron esas unidades
            $steps = app(ProductivityDetailService::class)->reprocessSteps($detail->production_order, $detail->productivity->model)->keyBy('key');
            $units = $this->integer('units');

            foreach (array_values($this->input('steps')) as $i => $key) {
                $step = $steps->get($key);

                if (! $step) {
                    $validator->errors()->add("steps.$i", 'El lote no lleva este paso en el proceso.');
                } elseif ($step['produced'] < $units) {
                    $validator->errors()->add("steps.$i", "{$step['name']} lleva {$step['produced']} unidades hechas: no se pueden devolver {$units}.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'units.min' => 'Debe ser al menos :min.',
            'units.max' => 'Debe ser máximo :max.',
            'steps.min' => 'Elige al menos un paso que se vuelve a hacer.',
            'note.max' => 'Se permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Registro de trabajo.',
            'units' => 'Unidades a reprocesar.',
            'steps' => 'Pasos que se vuelven a hacer.',
            'note' => 'Motivo.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
