<?php

namespace App\Http\Requests\Workflow\Subprocess;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use App\Models\WashTone;
use App\Traits\ValidatesWorkflowChain;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class SubprocessUpdateRequest extends FormRequest
{
    use ValidatesWorkflowChain;

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
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Subprocess::ITEM_ID],
            'process_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Subprocess::ITEM_ID],
            'description' => ['required', 'string', 'max:255', 'uppercase'],
            'settings' => ['required', 'array'],
            'settings.operations' => ['required', 'boolean'],
            'settings.operation_flow' => ['nullable', 'required_if_accepted:settings.operations', 'in:'.implode(',', Process::FLOWS)],
            'after_subprocesses' => ['nullable', 'array'],
            'after_subprocesses.*' => ['numeric', 'distinct', 'exists:subitems,id,item_id,'.Subprocess::ITEM_ID.',deleted_at,NULL'],
            'settings.in_technical_sheet' => ['required', 'boolean'],
            'settings.in_production_order' => ['required', 'boolean'],
            // Color con el que se reconoce en tablas y botones (#RRGGBB); no se repite entre procesos, subprocesos ni operaciones
            'settings.color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            // Cómo se mide en productividad; si no se envía, se hereda la del subproceso o la del proceso
            'settings.productivity_measurement' => ['nullable', 'in:lots,units'],
            // Por qué se multiplica el tiempo de la regla: nada, botones de la ficha o una cantidad que se digita al
            // programar (con su nombre); sin elegir, el de su subproceso
            'settings.time_multiplier' => ['nullable', 'in:none,buttons,manual'],
            // Por unidades: con un +1 por prenda o digitando el total al finalizar (SANDBLAST); sin elegir, el del subproceso
            'settings.units_entry' => ['nullable', 'in:plus,finish'],
            // Unidades al programar: reparto automático del lote o las digita quien programa (tandas); sin elegir, la del
            // subproceso
            'settings.schedule_units' => ['nullable', 'in:auto,typed'],
            // Una persona puede hacer varias de sus operaciones a la vez (bisturí y motor del mismo lote)
            // Solo si el lote hace alguno de sus subprocesos anteriores (CENTRIFUGADO 2 solo si hizo LAVADO 2)
            'settings.only_after_previous' => ['nullable', 'boolean'],
            // Condición de cada siguiente: solo con estos tonos de lavado de la ficha (PLANCHA → SEDA solo en NEGRO) o, con
            // except, con todos menos estos (SECADO 2 → SEDA menos NEGRO); con returns, el lote regresa a ese paso y vuelve
            // a pasar por él (LAVADO ADICIONAL ↩ CENTRIFUGADO 1)
            'settings.next_conditions' => ['nullable', 'array'],
            'settings.next_conditions.*.wash_tone_ids' => ['nullable', 'array'],
            'settings.next_conditions.*.except' => ['nullable', 'boolean'],
            'settings.next_conditions.*.returns' => ['nullable', 'boolean'],
            'settings.next_conditions.*.wash_tone_ids.*' => ['integer', 'distinct', 'exists:subitems,id,item_id,'.WashTone::ITEM_ID.',deleted_at,NULL'],
            'settings.time_multiplier_label' => ['nullable', 'required_if:settings.time_multiplier,manual', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.color.regex' => 'Debe ser un color en formato #RRGGBB.',
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

            // Las condiciones solo pueden ser de subprocesos que están en los siguientes, y tienen tonos o son un regreso
            $next = collect($this->input('after_subprocesses', []))->map(fn ($id) => (int) $id);
            foreach ((array) $this->input('settings.next_conditions', []) as $id => $condition) {
                if (! $next->contains((int) $id)) {
                    $validator->errors()->add('settings.next_conditions', 'Hay una condición para un subproceso que no está en los siguientes.');
                } elseif (empty($condition['wash_tone_ids']) && empty($condition['returns'])) {
                    $validator->errors()->add('settings.next_conditions', 'Cada condición debe tener tonos o ser un regreso.');
                }
            }

            if ($validator->errors()->isEmpty()) {
                $this->validateChain($validator, Subprocess::find($this->integer('id'))?->process()->first(), 'subprocess_flow', 'subprocesses', 'after_subprocesses', $this->integer('id'));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'subprocess_id' => 'Identificador del subproceso',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.operations' => 'Operaciones',
            'settings.operation_flow' => 'Flujo de operaciones',
            'after_subprocesses' => 'Subprocesos siguientes',
            'settings.in_technical_sheet' => 'Aplica a Ficha Técnica',
            'settings.in_production_order' => 'Aplica a Orden de Producción',
            'settings.color' => 'Color asociado',
            'settings.productivity_measurement' => 'Medición de productividad',
            'settings.time_multiplier' => 'Multiplicar el tiempo por',
            'settings.units_entry' => 'Cómo registra las unidades',
            'settings.time_multiplier_label' => 'Nombre de la cantidad',
            'settings.schedule_units' => 'Unidades al programar',
            'settings.only_after_previous' => 'Solo si hace el subproceso anterior',
            'settings.next_conditions' => 'Condiciones de los siguientes',
            'settings.next_conditions.*.wash_tone_ids' => 'Tonos',
            'settings.next_conditions.*.except' => 'Todos los tonos menos estos',
            'settings.next_conditions.*.returns' => 'Regresa',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
