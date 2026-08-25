<?php

namespace App\Http\Requests\Typification\SupplyType\Variant;

use App\Models\SupplyType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VariantExcelRequest extends FormRequest
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
            'supply_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . SupplyType::ITEM_ID]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'numeric' => 'Debe ser un valor numérico.',
            'exists' => 'No hay ningún registro.'
        ];
    }

    public function attributes(): array
    {
        return [
            'supply_type_id' => 'Identificador del tipo de insumo'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['supply_type_id' => $this->route('supply_type_id')]);
    }
}
