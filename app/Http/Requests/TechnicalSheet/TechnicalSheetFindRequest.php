<?php

namespace App\Http\Requests\TechnicalSheet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TechnicalSheetFindRequest extends FormRequest
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
            'id' => ['nullable', 'numeric', 'exists:technical_sheets,id', 'required_without_all:product_id,uuid', 'prohibits:product_id,uuid'],
            'product_id' => ['nullable', 'numeric', 'exists:technical_sheets,product_id', 'required_without_all:id,uuid', 'prohibits:id,uuid'],
            'uuid' => ['nullable', 'uuid', 'exists:technical_sheets,uuid', 'required_without_all:id,product_id', 'prohibits:id,product_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'numeric' => 'Debe ser un valor numérico.',
            'exists' => 'No hay ningún registro.',
            'required_without_all' => 'Es requerido cuando :values no estan presentes.',
            'uuid' => 'Debe ser un código uudid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la ficha técnica',
            'product_id' => 'Identificador del producto en la ficha técnica',
            'uuid' => 'Código uuid de la ficha técnica'
        ];
    }
}
