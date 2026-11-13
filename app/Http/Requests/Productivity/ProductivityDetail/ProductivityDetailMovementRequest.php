<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Movement;
use App\Models\Process;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Productividad de un movimiento: los registros de trabajo del lote en el proceso del movimiento.
 */
class ProductivityDetailMovementRequest extends FormRequest
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
            'movement_id' => ['required', 'integer', 'exists:movements,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $movement = Movement::with('production_order_detail')->find($this->integer('movement_id'));

            if (! $movement->model instanceof Process || ! $movement->production_order_detail) {
                $validator->errors()->add('movement_id', 'El movimiento no es de un proceso con referencia.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'movement_id' => 'Movimiento.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['movement_id' => $this->route('movement_id')]);
    }
}
