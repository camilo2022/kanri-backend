<?php

namespace App\Http\Requests\Productivity;

use App\Models\Productivity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Desactivar un turno: solo si todavía no ha iniciado y no tiene trabajo registrado.
 */
class ProductivityDeleteRequest extends FormRequest
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
            'id' => ['required', 'exists:productivity,id,deleted_at,NULL'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $shift = Productivity::find($this->input('id'));

            if ($shift->status !== Productivity::PENDING) {
                $validator->errors()->add('id', $shift->status === Productivity::IN_PROGRESS
                    ? 'El turno ya inició; no se puede desactivar.'
                    : 'El turno ya terminó; no se puede desactivar.');

                return;
            }

            if ($shift->productivity_details()->exists()) {
                $validator->errors()->add('id', 'El turno tiene programación o trabajo registrado; quítalos antes de desactivarlo.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'id' => 'Turno.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
