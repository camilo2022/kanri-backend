<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Productividad de una referencia (botón del listado de productos): los registros de trabajo de los lotes de su ficha.
 */
class ProductivityDetailTechnicalSheetRequest extends FormRequest
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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['products.find'];
    }

    public function rules(): array
    {
        return [
            'technical_sheet_id' => ['required', 'integer', 'exists:technical_sheets,id,deleted_at,NULL'],
        ];
    }

    public function attributes(): array
    {
        return [
            'technical_sheet_id' => 'Ficha técnica',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['technical_sheet_id' => $this->route('technical_sheet_id')]);
    }
}
