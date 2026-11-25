<?php

namespace App\Http\Requests\PensionFund;

use App\Models\PensionFund;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PensionFundUpdateRequest extends FormRequest
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
        return ['pension_funds.update'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . PensionFund::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:100', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . PensionFund::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255']
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del fondo de pensión',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
