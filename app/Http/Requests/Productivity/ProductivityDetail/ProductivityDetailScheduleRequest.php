<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Productivity;
use App\Models\TechnicalSheetDetail;
use App\Services\ProductivityDetailService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Programar en cadena un turno de Corte: las referencias en el orden en que se trabajan, cada una con los puestos que el
 * líder le quitó y los empleados por puesto si los cambió. Las horas las calcula el sistema; lo ya iniciado no se toca.
 * Con `preview` solo calcula: los problemas de la cadena vienen en la respuesta y no impiden ver la tabla.
 */
class ProductivityDetailScheduleRequest extends FormRequest
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
            'productivity_id' => ['required', 'integer', 'exists:productivity,id,deleted_at,NULL'],
            'preview' => ['nullable', 'boolean'],
            'production_orders' => ['present', 'array'],
            'production_orders.*.id' => ['required', 'integer', 'distinct', 'exists:production_orders,id'],
            'production_orders.*.removed' => ['nullable', 'array'],
            'production_orders.*.removed.*' => ['string'],
            'production_orders.*.employees' => ['nullable', 'array'],
            'production_orders.*.employees.*' => ['array', 'min:1'],
            'production_orders.*.employees.*.*' => ['integer'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $shift = Productivity::find($this->integer('productivity_id'));
            $service = app(ProductivityDetailService::class);
            $process = $shift->model;

            if (! $process instanceof Process || ! $service->programsChain($process)) {
                $validator->errors()->add('productivity_id', 'Este turno no se programa en cadena: elige el puesto y la hora de cada referencia.');

                return;
            }

            if ($shift->status === Productivity::FINISHED) {
                $validator->errors()->add('productivity_id', 'El turno ya terminó; su programación no se puede cambiar.');

                return;
            }

            foreach ($this->input('production_orders') as $index => $lot) {
                $production_order = ProductionOrder::find($lot['id']);

                if ($error = $service->branchError($production_order, $process)) {
                    $validator->errors()->add("production_orders.$index", $error);

                    continue;
                }

                if ($production_order->status === ProductionOrder::CANCELLED) {
                    $validator->errors()->add("production_orders.$index", 'La orden de producción está cancelada.');

                    continue;
                }

                $ready = TechnicalSheetDetail::where('technical_sheet_id', $production_order->technical_sheet_id)
                    ->where('model_type', Process::class)
                    ->where('model_id', $process->id)
                    ->whereIn('status', [TechnicalSheetDetail::REVIEW, TechnicalSheetDetail::APPROVED])
                    ->exists();

                if (! $ready) {
                    $validator->errors()->add("production_orders.$index", "{$process->name} aún no está en revisión ni aprobado en la ficha técnica de esta referencia: no se puede producir.");

                    continue;
                }

                if ($service->lotUnitsIn($production_order, $process) < 1) {
                    $validator->errors()->add("production_orders.$index", 'La orden de producción no tiene unidades en las tallas que hoy tiene la marca: no se puede trabajar.');
                }
            }

            if ($validator->errors()->isNotEmpty() || $this->boolean('preview')) {
                return;
            }

            foreach ($service->chainPlan($shift, $this->input('production_orders'))['errors'] as $key => $message) {
                $validator->errors()->add($key, $message);
            }
        });
    }

    public function messages(): array
    {
        return [
            'production_orders.*.employees.*.min' => 'Debe trabajar al menos una persona.',
        ];
    }

    public function attributes(): array
    {
        return [
            'productivity_id' => 'Turno.',
            'production_orders' => 'Referencias.',
            'production_orders.*.id' => 'Referencia.',
            'production_orders.*.removed' => 'Puestos quitados.',
            'production_orders.*.employees' => 'Empleados.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['productivity_id' => $this->route('productivity_id')]);
    }
}
