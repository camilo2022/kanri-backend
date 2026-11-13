<?php

namespace App\Http\Requests\Workflow\Subprocess;

use App\Models\Subprocess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubprocessQrRequest extends FormRequest
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
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . Subprocess::ITEM_ID . ',deleted_at,NULL'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // El QR se genera en el nivel más bajo: si el subproceso tiene operaciones, va en ellas y no aquí
            if (Subprocess::find($this->input('id'))?->operations()->exists()) {
                $validator->errors()->add('id', 'El subproceso tiene operaciones; el QR se genera desde cada operación.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del subproceso',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
