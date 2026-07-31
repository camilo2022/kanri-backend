<?php

namespace App\Http\Requests\TechnicalSheet\ProductionOrderDetail;

use App\Models\SupplyType;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Models\Trademark;
use App\Models\Variant;
use App\Models\WashTone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class ProductionOrderDetailSaveRequest extends FormRequest
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
            'id' => ['required', 'exists:production_orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => "Es obligatorio.\n",
            'exists' => 'No registrado en el sistema.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->input('production_order_details', []) as $i => $v) {
            $base = "production_order_details.$v[model_id]";

            $attributes["$base.id"] = "Id del detalle $i";
            $attributes["$base.model_type"] = "Modelo del detalle $i";
            $attributes["$base.model_id"] = "Identificador del detalle $i";
        }

        return $attributes;
    }



    public function withValidator($validator) {}
}
