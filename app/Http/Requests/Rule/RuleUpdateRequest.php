<?php

namespace App\Http\Requests\Rule;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Rule;
use App\Models\Subprocess;
use App\Services\PermissionService;
use App\Services\ProductivityDetailService;
use App\Services\StandardRuleService;
use App\Traits\ModelTypes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class RuleUpdateRequest extends FormRequest
{
    use ModelTypes;

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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['rules.update'];
    }

    public function rules(): array
    {
        return [
            'rule_id' => ['required', 'exists:subitems,id,item_id,'.Rule::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:150', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Rule::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.capacities' => ['required', 'array', 'min:1'],
            'settings.capacities.*.people' => ['required', 'integer', 'min:1', 'distinct'],
            'settings.capacities.*.time' => ['required', 'numeric', 'gt:0'],
            'settings.capacities.*.time_unit' => ['required', 'string', 'in:seg,min'],
            'relations.process' => ['required', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            'signature' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Se permite mínimo :min caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rule_id' => 'Identificador de la regla',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.capacities' => 'Capacidades',
            'settings.capacities.*.people' => 'Personas',
            'settings.capacities.*.time' => 'Tiempo',
            'settings.capacities.*.time_unit' => 'Unidad de tiempo',
            'settings.code' => 'Código',
        ];
    }

    public function withValidator($validator)
    {
        // Capacidades: por unidades una sola; por lotes, a más personas menos tiempo
        $validator->after(function ($validator) {
            $capacities = $this->input('settings.capacities');

            if (! is_array($capacities) || collect($validator->errors()->keys())->contains(fn ($key) => str_starts_with($key, 'settings.capacities'))) {
                return;
            }

            // La medición es la del alcance más específico de la regla (operación, subproceso o proceso), con herencia
            $scope = Operation::find($this->input('relations.operation'))
                ?? Subprocess::find($this->input('relations.subprocess'))
                ?? Process::find($this->input('relations.process'));

            if (! $scope || app(ProductivityDetailService::class)->measurementOf($scope) !== 'lots') {
                if (count($capacities) > 1) {
                    $validator->errors()->add('settings.capacities', 'Un puesto que no se mide por lotes tiene una sola capacidad.');
                }

                return;
            }

            $sorted = collect($capacities)
                ->map(fn ($capacity, $index) => [
                    'index' => $index,
                    'people' => (int) $capacity['people'],
                    'time' => (float) $capacity['time'],
                    'time_unit' => $capacity['time_unit'],
                    'seconds' => (float) $capacity['time'] * ($capacity['time_unit'] === 'min' ? 60 : 1),
                ])
                ->sortBy('people')
                ->values();

            foreach ($sorted as $position => $capacity) {
                $previous = $sorted[$position - 1] ?? null;

                if ($previous && $capacity['seconds'] >= $previous['seconds']) {
                    $validator->errors()->add(
                        "settings.capacities.{$capacity['index']}.time",
                        sprintf(
                            'Con %d personas el tiempo debe ser menor que con %d personas (%s %s).',
                            $capacity['people'],
                            $previous['people'],
                            rtrim(rtrim(number_format($previous['time'], 2, ',', '.'), '0'), ','),
                            $previous['time_unit']
                        )
                    );
                }
            }
        });

        $validator->after(function ($validator) {
            $item = DB::table('items')->where('id', Rule::ITEM_ID)->first();

            $weights = (array) (json_decode($item->settings ?? '{}')->weights ?? []);
            $models = $this->getModelTypesFromRules();
            $relations = (array) $this->input('relations', []);

            unset($relations['process'], $relations['subprocess'], $relations['operation']);

            foreach ($relations as $key => $value) {
                if (! array_key_exists($key, $weights)) {
                    $validator->errors()->add("relations.$key", 'No asociado al item.');

                    continue;
                }

                if (blank($value)) {
                    continue;
                }

                $model_class = $models[$key]['model'] ?? null;

                if (! $model_class) {
                    $validator->errors()->add("relations.$key", 'Modelo no configurado.');

                    continue;
                }

                if (! $model_class::whereKey($value)->exists()) {
                    $validator->errors()->add("relations.$key", 'No está registrado.');
                }
            }

            if ($this->input('relations.subprocess')) {
                $process = Process::find($this->input('relations.process'));

                if (! $process || ! $process->subprocesses()->whereKey($this->input('relations.subprocess'))->exists()) {
                    $validator->errors()->add('relations.subprocess', 'No pertenece al proceso seleccionado.');

                    return;
                }
            }

            if ($this->input('relations.operation')) {
                if (! $this->input('relations.subprocess')) {
                    $validator->errors()->add('relations.subprocess', 'Es obligatorio si eliges una operación.');

                    return;
                }

                $subprocess = Subprocess::find($this->input('relations.subprocess'));

                if (! $subprocess || ! $subprocess->operations()->whereKey($this->input('relations.operation'))->exists()) {
                    $validator->errors()->add('relations.operation', 'No pertenece al subproceso seleccionado.');
                }
            }

            if (app(StandardRuleService::class)->activeSignatures((int) $this->route('id'))->contains($this->input('signature'))) {
                $validator->errors()->add('signature', 'Ya existe una regla con esta misma combinación de relaciones.');
            }
        });
    }

    protected function prepareForValidation()
    {
        $relations = collect($this->input('relations', []))
            ->filter(fn ($value) => filled($value))
            ->sortKeys();

        $this->merge([
            'rule_id' => $this->route('id'),
            'signature' => $relations->map(fn ($value, $key) => "$key:$value")->implode('|'),
        ]);
    }
}
