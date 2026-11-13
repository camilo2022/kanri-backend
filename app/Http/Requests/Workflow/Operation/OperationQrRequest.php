<?php

namespace App\Http\Requests\Workflow\Operation;

use App\Models\Operation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class OperationQrRequest extends FormRequest
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
        // La operación es el nivel más bajo: siempre puede tener QR mientras esté activa
        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . Operation::ITEM_ID . ',deleted_at,NULL'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la operación',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
