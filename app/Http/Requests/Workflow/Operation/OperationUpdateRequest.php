<?php

namespace App\Http\Requests\Workflow\Operation;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use App\Traits\ValidatesWorkflowChain;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class OperationUpdateRequest extends FormRequest
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
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Operation::ITEM_ID],
            'subprocess_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Subprocess::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Operation::ITEM_ID],
            'description' => ['required', 'string', 'max:255', 'uppercase'],
            'settings' => ['required', 'array'],
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
            'settings.time_multiplier_label' => ['nullable', 'required_if:settings.time_multiplier,manual', 'string', 'max:40'],
            'after_operations' => ['nullable', 'array'],
            'after_operations.*' => ['numeric', 'distinct', 'exists:subitems,id,item_id,'.Operation::ITEM_ID.',deleted_at,NULL'],
            // Subproceso en paralelo: operaciones del mismo subproceso que deben estar hechas antes (RELOJERA depende de PARCHE)
            'before_operations' => ['nullable', 'array'],
            'before_operations.*' => ['numeric', 'distinct', 'exists:subitems,id,item_id,'.Operation::ITEM_ID.',deleted_at,NULL'],
            // Subprocesos siguientes en la cadena del proceso: la operación sale de su subproceso y abre una línea (RESINA →
            // LAVADO RESINA)
            'after_subprocesses' => ['nullable', 'array'],
            'after_subprocesses.*' => ['numeric', 'distinct', 'exists:subitems,id,item_id,'.Subprocess::ITEM_ID.',deleted_at,NULL'],
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

            if ($validator->errors()->isEmpty()) {
                $this->validateChain($validator, Operation::find($this->integer('id'))?->subprocess()->first(), 'operation_flow', 'operations', 'after_operations', $this->integer('id'));
                $this->validateOperationExits($validator, Operation::find($this->integer('id'))?->subprocess()->first());
                $this->validateDependencies($validator, Operation::find($this->integer('id'))?->subprocess()->first(), $this->integer('id'));
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
            'after_operations' => 'Operaciones siguientes',
            'before_operations' => 'Depende de',
            'after_subprocesses' => 'Subprocesos siguientes',
            'settings.in_technical_sheet' => 'Aplica a Ficha Técnica',
            'settings.in_production_order' => 'Aplica a Orden de Producción',
            'settings.color' => 'Color asociado',
            'settings.productivity_measurement' => 'Medición de productividad',
            'settings.time_multiplier' => 'Multiplicar el tiempo por',
            'settings.units_entry' => 'Cómo registra las unidades',
            'settings.time_multiplier_label' => 'Nombre de la cantidad',
            'settings.schedule_units' => 'Unidades al programar',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
