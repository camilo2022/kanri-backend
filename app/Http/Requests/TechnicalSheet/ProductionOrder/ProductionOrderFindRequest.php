<?php

namespace App\Http\Requests\TechnicalSheet\ProductionOrder;

use App\Services\PermissionService;
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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['technical_sheets.production_orders.find'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric', 'exists:production_orders,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la orden de producción',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
