<?php

namespace App\Http\Requests\Workflow\Operation;

use App\Models\Process;
use App\Models\Operation;
use App\Models\Subprocess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class OperationUpdateRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric', 'string', 'exists:subitems,id,item_id,' . Operation::ITEM_ID],
            'subprocess_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Subprocess::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . Operation::ITEM_ID],
            'description' => ['required', 'string', 'max:255', 'uppercase'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'exists' => 'No está registrado.'
        ];
    }

    public function attributes(): array
    {
        return [
            'subprocess_id' => 'Identificador del subproceso',
            'name' => 'Nombre',
            'description' => 'Descripción',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
