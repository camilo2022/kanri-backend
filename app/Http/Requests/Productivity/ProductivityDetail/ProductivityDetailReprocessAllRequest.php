<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\ProductivityDetail;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Consultar los pasos a los que se puede devolver un reproceso desde un registro de trabajo ya iniciado.
 */
class ProductivityDetailReprocessAllRequest extends FormRequest
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
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $detail = ProductivityDetail::with(['productivity' => ['model']])->find($this->integer('id'));
            $process = $detail->productivity?->model;

            if (! ($process->settings->reprocesses ?? false)) {
                $validator->errors()->add('id', ($process->name ?? 'El proceso').' no permite reprocesos.');
            } elseif (! isset($detail->settings->scanned_at)) {
                $validator->errors()->add('id', 'El trabajo aún no se ha iniciado: no tiene unidades para reprocesar.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'id' => 'Registro de trabajo.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
