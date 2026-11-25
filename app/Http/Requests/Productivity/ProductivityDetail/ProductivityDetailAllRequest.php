<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Listar los registros de trabajo de un turno activo (ojito de la tabla de turnos).
 */
class ProductivityDetailAllRequest extends FormRequest
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
        return ['productivity.find'];
    }

    public function rules(): array
    {
        return [
            'productivity_id' => ['required', 'exists:productivity,id,deleted_at,NULL'],
        ];
    }

    public function attributes(): array
    {
        return [
            'productivity_id' => 'Turno.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['productivity_id' => $this->route('productivity_id')]);
    }
}
