<?php

namespace App\Http\Requests\Productivity;

use App\Models\Productivity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Restaurar (activar) un turno desactivado: no debe cruzarse con otro turno activo del mismo proceso.
 */
class ProductivityRestoreRequest extends FormRequest
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
            'id' => ['required', 'exists:productivity,id,deleted_at,NOT_NULL'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $shift = Productivity::onlyTrashed()->find($this->input('id'));

            if (Productivity::where('model_type', $shift->model_type)
                ->where('model_id', $shift->model_id)
                ->whereDate('date', $shift->date)
                ->where('start_time', '<', $shift->end_time)
                ->where('end_time', '>', $shift->start_time)
                ->exists()
            ) {
                $validator->errors()->add('id', 'Ya hay otro turno activo que se cruza con este horario; ajústalo antes de activar este.');
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
