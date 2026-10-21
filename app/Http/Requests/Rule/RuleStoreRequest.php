<?php

namespace App\Http\Requests\Rule;

use App\Models\Process;
use App\Models\Rule;
use App\Models\Subprocess;
use App\Traits\ModelTypes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class RuleStoreRequest extends FormRequest
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
        return true;
    }

    public function rules(): array
    {
        logger($this);

        return [
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:150', 'unique:subitems,name,NULL,id,item_id,'.Rule::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.people' => ['required', 'numeric', 'min:1'],
            'settings.time_unit' => ['required', 'string', 'in:seg,min'],
            'settings.time' => ['required', 'numeric', 'min:1'],
            'relations.process' => ['required', 'exists:subitems,id,item_id,'.Process::ITEM_ID],
            'signature' => ['required', 'unique:subitems,settings->signature,NULL,id,item_id,'.Rule::ITEM_ID],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'settings.time.min' => 'Debe ser mayor o igual a :min.',
            'settings.people.min' => 'Debe ser mayor o igual a :min.',
            'name.min' => 'Se permite mínimo :min caracteres.',
            'max' => 'Se permite máximo :max caracteres.',
            'unique' => 'Ya está registrado.',
            'uppercase' => 'Debe de ingresar mayúsculas.',
            'array' => 'Debe ser un arreglo.',
            'numeric' => 'Debe ser un valor numérico.',
            'digits_between' => 'Debe tener entre 2 y 5 dígitos.',
            'signature.unique' => 'Ya existe una regla con esta misma combinación de relaciones.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.code' => 'Código',
        ];
    }

    public function withValidator($validator)
    {
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

            $models = $this->getModelTypesFromRules();

            $class_to_key = collect($models)->mapWithKeys(fn ($config, $key) => [$config['model'] => $key]);

            $rule_ids = DB::table('subitems')
                ->where('item_id', Rule::ITEM_ID)
                ->whereNull('deleted_at')
                ->pluck('id');

            $existing_signatures = DB::table('model_has_subitems')
                ->whereIn('subitem_id', $rule_ids)
                ->get(['subitem_id', 'model_type', 'model_id'])
                ->groupBy('subitem_id')
                ->map(function ($rows) use ($class_to_key) {
                    return $rows
                        ->mapWithKeys(function ($row) use ($class_to_key) {
                            $class = Relation::getMorphedModel($row->model_type) ?? $row->model_type;
                            $key = $class_to_key[$class] ?? null;

                            return $key ? [$key => $row->model_id] : [];
                        })
                        ->sortKeys()
                        ->map(fn ($value, $key) => "$key:$value")
                        ->implode('|');
                });

            if ($existing_signatures->contains($this->input('signature'))) {
                $validator->errors()->add('signature', 'Ya existe una regla con esta misma combinación de relaciones.');
            }
        });
    }

    protected function prepareForValidation()
    {
        $relations = collect($this->input('relations', []))
            ->filter(fn ($value) => filled($value))
            ->sortKeys();

        logger($relations);

        $this->merge([
            'signature' => $relations->map(fn ($value, $key) => "$key:$value")->implode('|'),
        ]);
    }
}
