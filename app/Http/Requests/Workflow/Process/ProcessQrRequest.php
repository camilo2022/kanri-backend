<?php

namespace App\Http\Requests\Workflow\Process;

use App\Models\Process;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessQrRequest extends FormRequest
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
        return ['workflow.processes.qr'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . Process::ITEM_ID . ',deleted_at,NULL'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (Process::find($this->input('id'))?->subprocesses()->exists()) {
                $validator->errors()->add('id', 'El proceso tiene subprocesos; el QR se genera desde cada subproceso.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del proceso',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
