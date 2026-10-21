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
            'settings.paragraph' => ['sometimes'],
            'settings.productivity_measurement' => ['required_if:settings.in_production_order,true'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'required_if' => 'Es obligatorio.',
            'array' => 'Debe ser una lista de datos.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'exists' => 'No está registrado.',
            'in' => 'El valor seleccionado no es válido.',
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
            'settings.paragraph' => 'Lugar de producción',
            'settings.productivity_measurement' => 'Medición de la productividad',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $settings = $this->input('settings');

            if (! ($settings['in_production_order'] ?? false) || ($settings['paragraph'] ?? null) !== 'production_place') {
                return;
            }

            $exists = Process::query()->where('item_id', Process::ITEM_ID)
                ->where('id', '!=', $this->route('id'))
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(settings, '$.in_production_order')) = 'true'")
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(settings, '$.paragraph')) = ?", ['production_place'])
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'settings.paragraph',
                    'Ya existe otro proceso asignado al lugar de producción de la orden de producción.'
                );
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
