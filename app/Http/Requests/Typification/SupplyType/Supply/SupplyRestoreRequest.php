<?php

namespace App\Http\Requests\Typification\SupplyType\Supply;

use App\Models\Supply;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SupplyRestoreRequest extends FormRequest
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
        return ['typification.supply_types.supplies.restore'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', Rule::exists('subitems', 'id')->where('item_id', Supply::ITEM_ID)->whereNotNull('deleted_at')]
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del insumo'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
