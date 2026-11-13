<?php

namespace App\Http\Requests\Workflow\Process;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

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
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Process::ITEM_ID],
            'description' => ['required', 'string', 'max:255', 'uppercase'],
            'after_processes' => ['nullable', 'array'],
            'after_processes.*' => ['nullable', 'numeric', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.subprocesses' => ['required', 'boolean'],
            'settings.subprocess_flow' => ['nullable', 'required_if_accepted:settings.subprocesses', 'in:'.implode(',', Process::FLOWS)],
            'settings.in_technical_sheet' => ['required', 'boolean'],
            'settings.in_production_order' => ['required', 'boolean'],
            // Color con el que se reconoce en tablas y botones (#RRGGBB); no se repite entre procesos, subprocesos ni operaciones
            'settings.color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'settings.paragraph' => ['sometimes'],
            'settings.productivity_measurement' => ['required_if:settings.in_production_order,true'],
            // Cualquier puesto del proceso trabaja la referencia (Confección: los módulos se reparten el lote); si no, solo
            // los subprocesos y operaciones que marca la ficha
            'settings.any_workstation' => ['nullable', 'boolean'],
            // Se pueden registrar reprocesos entre sus subprocesos y operaciones (Lavandería)
            'settings.reprocesses' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.color.regex' => 'Debe ser un color en formato #RRGGBB.',
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
            'settings.subprocess_flow' => 'Flujo de subprocesos',
            'settings.in_technical_sheet' => 'Aplica a Ficha Técnica',
            'settings.in_production_order' => 'Aplica a Orden de Producción',
            'settings.color' => 'Color asociado',
            'settings.paragraph' => 'Lugar de producción',
            'settings.productivity_measurement' => 'Medición de la productividad',
            'settings.any_workstation' => 'Cualquier puesto trabaja la referencia',
            'settings.reprocesses' => 'Permite reprocesos',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // El color no se repite nunca entre procesos, subprocesos y operaciones
            if ($color = $this->input('settings.color')) {
                $taken = DB::table('subitems')
                    ->whereIn('item_id', [Process::ITEM_ID, Subprocess::ITEM_ID, Operation::ITEM_ID])
                    ->whereNull('deleted_at')
                ->where('id', '<>', $this->route('id'))
                    ->whereRaw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(settings, '$.color'))) = ?", [strtoupper($color)])
                    ->value('name');

                if ($taken) {
                    $validator->errors()->add('settings.color', "Ese color ya lo tiene {$taken}: elige otro.");
                }
            }

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
