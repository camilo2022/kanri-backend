<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\ProductivityDetail;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Anular un reproceso registrado por error (por su posición en el registro de trabajo).
 */
class ProductivityDetailReprocessDeleteRequest extends FormRequest
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
        return ['productivity.reprocess'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:productivity_details,id'],
            'index' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $reprocesses = (array) (ProductivityDetail::find($this->integer('id'))->settings->reprocesses ?? []);

            if (! array_key_exists($this->integer('index'), $reprocesses)) {
                $validator->errors()->add('index', 'Ese reproceso no existe en el trabajo.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'id' => 'Registro de trabajo.',
            'index' => 'Reproceso.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id'), 'index' => $this->route('index')]);
    }
}
