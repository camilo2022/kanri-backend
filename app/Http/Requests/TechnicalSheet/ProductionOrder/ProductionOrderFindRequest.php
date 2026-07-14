<?php

namespace App\Http\Requests\TechnicalSheet\ProductionOrder;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductionOrderFindRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'numeric', 'exists:production_orders,id', 'required_without_all:technical_sheet_id', 'prohibits:technical_sheet_id'],
            'technical_sheet_id' => ['nullable', 'numeric', 'exists:technical_sheets,id', 'required_without_all:id', 'prohibits:id'],
        ];
    }

    public function messages(): array
    {
        return [
            'numeric' => 'Debe ser un valor numérico.',
            'exists' => 'No hay ningún registro.',
            'required_without_all' => 'Es requerido cuando :values no estan presentes.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la orden de producción',
            'technical_sheet_id' => 'Identificador de la ficha tecnica en la orden de producción'
        ];
    }
}
