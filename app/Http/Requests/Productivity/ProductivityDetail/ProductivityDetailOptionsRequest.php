<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Process;
use App\Models\Productivity;
use App\Services\ProductivityDetailService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Opciones para programar un turno (referencias con unidades por hacer, puestos con sus empleados y tiempo por unidad
 * de cada referencia en cada puesto): solo procesos por unidades.
 */
class ProductivityDetailOptionsRequest extends FormRequest
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
            'productivity_id' => ['required', 'exists:productivity,id,deleted_at,NULL'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $shift = Productivity::find($this->input('productivity_id'));

            if (! $shift->model instanceof Process || ! app(ProductivityDetailService::class)->workstations($shift->model)->contains(fn ($item) => app(ProductivityDetailService::class)->schedulable($shift->model, $item['model_type']::find($item['model_id'])))) {
                $validator->errors()->add('productivity_id', 'La programación solo aplica a procesos con puestos que tengan medición (por lote o por unidades).');

                return;
            }

            // Solo se programa un turno que todavía no ha empezado
            if ($shift->status === Productivity::FINISHED) {
                $validator->errors()->add('productivity_id', 'El turno ya terminó; su programación no se puede cambiar.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'productivity_id' => 'Turno.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['productivity_id' => $this->route('productivity_id')]);
    }
}
