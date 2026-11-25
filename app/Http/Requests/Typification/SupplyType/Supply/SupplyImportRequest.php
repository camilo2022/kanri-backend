<?php

namespace App\Http\Requests\Typification\SupplyType\Supply;

use App\Models\SupplyType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplyImportRequest extends FormRequest
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
        return ['typification.supply_types.supplies.import'];
    }

    public function rules(): array
    {
        return [
            'supply_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.SupplyType::ITEM_ID],
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supply_type_id' => 'Tipo de insumo',
            'file' => 'Archivo',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['supply_type_id' => $this->route('supply_type_id')]);
    }
}
