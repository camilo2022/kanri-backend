<?php

namespace App\Http\Requests\Rule;

use App\Models\Rule;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RuleDeleteRequest extends FormRequest
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
        return ['rules.delete'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,deleted_at,NULL,item_id,'.Rule::ITEM_ID],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del tipo de trasero',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
