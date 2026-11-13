<?php

namespace App\Http\Requests\Movement\ProductionOrderDetail;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductionOrderDetailAllRequest extends FormRequest
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
            'production_order_detail_id' => ['sometimes', 'numeric', 'exists:production_order_details,id'],
            'has_destination' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'production_order_detail_id' => 'Identificado del detalle de la orden de producción.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['has_destination' => $this->boolean('has_destination')]);
    }
}
