<?php

namespace App\Http\Requests\TechnicalSheetDetail;

use App\Models\SupplyType;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Models\Trademark;
use App\Models\Variant;
use App\Models\WashTone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class TechnicalSheetDetailSaveRequest extends FormRequest
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
            'id' => ['required', 'exists:technical_sheets,id'],

        ];
    }

    public function messages(): array
    {
        return [
            'required' => "Es obligatorio.\n",
            'exists' => 'No registrado en el sistema.',
            'unique' => 'El registro debe ser unico.',
            'string' => 'Debe ser una cadena de caracteres.',
            'max' => 'No debe exceder los :max caracteres.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'array' => 'Debe ser una lista de datos.',
            'boolean' => 'Debe se un valor falso/verdadero',
            'numeric' => 'Debe ser un valor numérico.',
            'image' => 'Debe ser una imagen.',
            'photo_d.max' => 'Se permite máximo :max KB.',
            'photo_t.max' => 'Se permite máximo :max KB.'
        ];
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->input('technical_sheet_details', []) as $i => $v) {
            $base = "technical_sheet_details.$v[model_id]";

            $attributes["$base.id"] = "Id del proceso $i";
            $attributes["$base.model_type"] = "Modelo del proceso $i";
            $attributes["$base.model_id"] = "Identificador del proceso $i";
            $attributes["$base.status"] = "Estado del proceso $i";

            $attributes["$base.settings.dinamic.header"] = "Encabezado del proceso $i";
            $attributes["$base.settings.dinamic.body"] = "Estructura de la tabla dinámica del proceso $i";
            $attributes["$base.settings.dinamic.values"] = "Valores de la tabla dinámica del proceso $i";
            $attributes["$base.settings.dinamic.insert_values"] = "Permiso de inserción dinámica del proceso $i";

            $attributes["$base.settings.static.header"] = "Encabezado de la tabla estática del proceso $i";
            $attributes["$base.settings.static.body"] = "Estructura de la tabla estática del proceso $i";
            $attributes["$base.settings.static.values"] = "Valores de la tabla estática del proceso $i";
            $attributes["$base.settings.static.insert_values"] = "Permiso de inserción estática del proceso $i";
        }

        return $attributes;
    }



    public function withValidator($validator) {}
}
