<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Productivity;
use App\Models\ProductivityDetail;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Quitar un registro programado: solo antes de que inicie el turno y si la estación aún no lo inició.
 */
class ProductivityDetailDeleteRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:productivity_details,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $detail = ProductivityDetail::with('productivity')->find($this->integer('id'));

            if (isset($detail->settings->scanned_at)) {
                $validator->errors()->add('id', 'El registro ya se inició en la estación; no se puede quitar.');

                return;
            }

            if ($detail->productivity->status === Productivity::FINISHED) {
                $validator->errors()->add('id', 'El turno ya terminó; su programación no se puede cambiar.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'id' => 'Registro programado.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
