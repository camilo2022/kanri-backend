<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\TechnicalSheetDetail;
use App\Services\ProductivityDetailService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Escaneo de la ficha técnica en el puesto: el puesto y su turno abierto, la orden de producción escaneada (uuid), que
 * el proceso esté «En revisión» o «Aprobado» en la ficha técnica y que la ficha lleve este puesto.
 */
class ProductivityDetailReferenceRequest extends FormRequest
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
            'production_order' => ['required', 'string', 'uuid', 'exists:production_orders,uuid,deleted_at,NULL'],
            // Con varios grupos programados para el lote en el puesto: el registro programado del grupo que eligió la tablet
            'group' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = app(ProductivityDetailService::class);
            $context = $service->workstationContext((string) $this->input('token'));

            if ($context['error']) {
                $validator->errors()->add('token', $context['error']);

                return;
            }

            $production_order = ProductionOrder::where('uuid', $this->input('production_order'))->first();
            $process = $context['process'];

            if ($error = $service->branchError($production_order, $process)) {
                $validator->errors()->add('production_order', $error);

                return;
            }

            $ready = TechnicalSheetDetail::where('technical_sheet_id', $production_order->technical_sheet_id)
                ->where('model_type', Process::class)
                ->where('model_id', $process->id)
                ->whereIn('status', [TechnicalSheetDetail::REVIEW, TechnicalSheetDetail::APPROVED])
                ->exists();

            if (! $ready) {
                $validator->errors()->add('production_order', "{$process->name} aún no está en revisión ni aprobado en la ficha técnica de esta referencia: no se puede producir.");

                return;
            }

            if ($error = $service->routeError($production_order, $context['workstation'], $process)) {
                $validator->errors()->add('production_order', $error);

                return;
            }

            // Solo se trabaja lo programado: desde su hora o antes, si se puede adelantar
            if ($service->schedulable($process, $context['workstation'])) {
                // Sin programación en el puesto, se puede iniciar cualquier referencia lista
                if ($service->scheduledDetails($context['shift'], $context['workstation'], $production_order)->isEmpty()
                    && $service->workstationProgrammed($context['shift'], $context['workstation'])) {
                    $validator->errors()->add('production_order', "La referencia no está programada para {$context['workstation']->name} en este turno.");

                    return;
                }

                if ($error = $service->scheduleWindowError($context['shift'], $context['workstation'], $production_order)) {
                    $validator->errors()->add('production_order', $error);

                    return;
                }
            }

            // Lo programado antes debe haber soltado prendas
            if ($service->programmable($process, $context['workstation'])) {
                $scheduled = $service->scheduledDetail($context['shift'], $context['workstation'], $production_order);
                $limit = $service->unitsLimit($context['shift'], $production_order, $context['workstation'], $process, $scheduled ? $service->scheduledStart($scheduled) : now());

                if ($error = $service->noUnitsError($limit, $production_order)) {
                    $validator->errors()->add('production_order', $error);
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'production_order.uuid' => 'El código escaneado no es de una orden de producción.',
            'production_order.exists' => 'La orden de producción no existe o está desactivada.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'Código QR del puesto de trabajo',
            'production_order' => 'Orden de producción escaneada',
            'group' => 'Grupo programado',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'token' => $this->route('token'),
            'production_order' => $this->route('uuid'),
        ]);
    }
}
