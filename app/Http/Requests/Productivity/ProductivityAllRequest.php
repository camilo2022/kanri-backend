<?php

namespace App\Http\Requests\Productivity;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductivityAllRequest extends FormRequest
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
            'model_type' => ['required', 'string', 'in:'.implode(',', [Process::class, Subprocess::class, Operation::class])],
            'model_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.match ($this->input('model_type')) {
                Process::class => Process::ITEM_ID,
                Subprocess::class => Subprocess::ITEM_ID,
                Operation::class => Operation::ITEM_ID,
                default => 0,
            }],
            'per_page' => ['nullable', 'numeric'],
            'page' => ['nullable', 'numeric'],
            'column' => ['nullable', 'string', 'in:id,date,start_time,end_time,created_at,updated_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'exists' => 'No hay ningún registro.',
        ];
    }

    public function attributes(): array
    {
        return [
            'model_type' => 'Tipo de elemento (proceso, subproceso u operación).',
            'model_id' => 'Identificador del elemento.',
            'per_page' => 'Registros por página.',
            'page' => 'N° de página.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
        ];
    }
}
