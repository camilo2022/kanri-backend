<?php

namespace App\Http\Requests\TechnicalSheet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Ficha de procesos en PDF: sale de la ficha técnica (su uuid), sin orden de producción.
 */
class TechnicalSheetProcessPdfRequest extends FormRequest
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
            'uuid' => ['required', 'string', 'uuid', 'exists:technical_sheets,uuid,deleted_at,NULL'],
        ];
    }

    public function attributes(): array
    {
        return [
            'uuid' => 'Código UUID de la ficha técnica',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['uuid' => $this->route('uuid')]);
    }
}
