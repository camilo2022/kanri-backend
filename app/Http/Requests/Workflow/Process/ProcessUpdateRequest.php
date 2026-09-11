<?php

namespace App\Http\Requests\Workflow\Process;

use App\Models\Process;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessUpdateRequest extends FormRequest
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
            'id' => ['required', 'numeric', 'string', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Process::ITEM_ID],
            'description' => ['required', 'string', 'max:255', 'uppercase'],
            'after_processes' => ['nullable', 'array'],
            'after_processes.*' => ['nullable', 'numeric', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.subprocesses' => ['required', 'boolean'],
            'settings.in_technical_sheet' => ['required', 'boolean'],
            'settings.in_production_order' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'array' => 'Debe ser una lista de datos.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del proceso',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'after_processes' => 'Procesos Posteriores',
            'settings' => 'Configuraciones',
            'settings.subprocesses' => 'Subprocesos',
            'settings.in_technical_sheet' => 'Aplica a Ficha Técnica',
            'settings.in_production_order' => 'Aplica a Orden de Producción',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
