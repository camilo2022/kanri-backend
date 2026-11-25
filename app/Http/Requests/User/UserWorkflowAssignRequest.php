<?php

namespace App\Http\Requests\User;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserWorkflowAssignRequest extends FormRequest
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
        return ['users.workflow.assign'];
    }

    public function rules(): array
    {

        return [
            'id' => ['required', 'numeric', 'exists:users,id'],
            'model_type' => ['required', 'string', 'in:'.implode(',', [Process::class, Subprocess::class, Operation::class])],
            'model_id' => ['required', 'numeric'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del usuario',
            'model_type' => 'Tipo de modelo',
            'model_id' => 'Identificador del elemento',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
