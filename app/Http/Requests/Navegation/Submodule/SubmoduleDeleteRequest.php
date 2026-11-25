<?php

namespace App\Http\Requests\Navegation\Submodule;

use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmoduleDeleteRequest extends FormRequest
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
        return ['navegation.modules.submodules.delete'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:submodules,id,deleted_at,NULL'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del Submódulo'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
