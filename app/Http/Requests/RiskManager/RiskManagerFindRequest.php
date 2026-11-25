<?php

namespace App\Http\Requests\RiskManager;

use App\Models\RiskManager;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RiskManagerFindRequest extends FormRequest
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
        return ['risk_managers.find'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . RiskManager::ITEM_ID],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la empresa administradora de riesgos'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
