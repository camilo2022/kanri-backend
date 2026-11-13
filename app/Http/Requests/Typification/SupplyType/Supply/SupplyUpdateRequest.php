<?php

namespace App\Http\Requests\Typification\SupplyType\Supply;

use App\Models\SupplyType;
use App\Models\Supply;
use App\Traits\ModelTypes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator as Valid;

class SupplyUpdateRequest extends FormRequest
{
    use ModelTypes;

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
            'id' => ['required', 'exists:subitems,id,item_id,' . Supply::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . Supply::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255',],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del insumo',
            'name' => 'Nombre',
            'description' => 'Descripción',
        ];
    }



    protected function resolveRule(string $rule): string
    {
        if (!is_string($rule)) {
            return $rule;
        }

        preg_match_all(
            '/([A-Za-z\\\\]+)::([A-Z_]+)/',
            $rule,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {

            $fullConstant = "{$match[1]}::{$match[2]}";

            if (defined($fullConstant)) {

                $rule = str_replace(
                    $fullConstant,
                    constant($fullConstant),
                    $rule
                );
            }
        }

        return $rule;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // El tipo es el que el insumo ya tiene: editar no lo cambia
            $supply_type = Supply::with(['supply_type'])->find($this->route('id'))?->supply_type->first();
            $rules = [];

            foreach ($supply_type?->settings?->form ?? [] as $column) {

                $field = $column->field ?? null;

                if (!$field) {
                    continue;
                }

                $group = isset($column->model) ? 'class' : 'values';

                $fieldRules = [];

                foreach ($column->rules ?? [] as $rule) {
                    $fieldRules[] = $this->resolveRule($rule);
                }

                if (($column->cardinality ?? 'single') === 'multiple') {
                    $rules["$group.$field"] = ['array'];

                    foreach ($fieldRules as $rule) {
                        $rules["$group.$field.*"][] = $rule;
                    }
                } else {
                    $rules["$group.$field"] = $fieldRules;
                }
            }

            $rowValidator = Valid::make(
                $this->all(),
                $rules,
                $this->messages()
            );

            if ($rowValidator->fails()) {
                foreach ($rowValidator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            $models = collect($this->getModelTypes());

            foreach ($supply_type?->settings?->form ?? [] as $column) {
                $param = $models->firstWhere('model', $models->firstWhere('model', $column->model ?? null)['param'] ?? null);
                $param_id = $param ? ($column->{$param['field']} ?? null) : null;

                if (! $param_id) {
                    continue;
                }

                foreach (Arr::wrap($this->input("class.$column->field")) as $key => $id) {
                    $belongs = $column->model::find($id)
                        ?->{Str::snake(class_basename($param['model']))}()
                        ->where('model_has_subitems.subitem_id', $param_id)
                        ->exists();

                    if (! $belongs) {
                        $validator->errors()->add(
                            ($column->cardinality ?? 'single') === 'multiple' ? "class.$column->field.$key" : "class.$column->field",
                            'No pertenece al '.Str::lower($param['label']).' configurado.'
                        );
                    }
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }

    protected function passedValidation()
    {
        // settings guarda solo los valores ya validados de los campos del formulario que no son de catálogo
        $fields = collect(data_get(Supply::with(['supply_type'])->find($this->route('id'))?->supply_type->first(), 'settings.form', []))
            ->filter(fn ($column) => ! isset($column->model))
            ->pluck('field')
            ->all();

        $this->merge(['settings' => ['values' => Arr::only($this->input('values', []), $fields)]]);
    }
}
