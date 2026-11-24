<?php

namespace App\Http\Requests\Typification\SupplyType\Supply;

use App\Models\SupplyType;
use App\Services\SupplyService;
use App\Traits\ModelTypes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator as Valid;

class SupplySaveRequest extends FormRequest
{
    use ModelTypes;

    public array $duplicates = [];

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
            'supplies' => ['required', 'array'],
            'supplies.*' => ['required', 'array'],
            'supplies.*.name' => ['required', 'min:4', 'max:50'],
            'supplies.*.description' => ['required', 'uppercase', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplies' => 'Supplyes',
        ];
    }

    protected function resolveRule(string $rule): string
    {
        if (! is_string($rule)) {
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

    protected function dynamicRules(): array
    {
        $supply_type = SupplyType::find($this->input('supply_type_id'));

        $arrayRules = [
            'required',
            'nullable',
            'min',
            'max',
            'size',
        ];

        $rules = [];

        foreach ($supply_type?->settings?->form ?? [] as $column) {

            $field = $column->field ?? null;

            if (! $field || in_array($column->type ?? null, SupplyService::SPECIAL_TYPES)) {
                continue;
            }

            $group = isset($column->model) ? 'class' : 'settings.values';

            $fieldRules = [];

            foreach ($column->rules ?? [] as $rule) {
                $rule = $this->resolveRule($rule);

                if (is_string($rule) && str_starts_with($rule, 'unique')) {

                    $uniqueFields[] = $field;

                    continue;
                }

                $fieldRules[] = $rule;
            }

            if (($column->cardinality ?? 'single') === 'multiple') {
                $rules["$group.$field"] = ['array'];

                foreach ($fieldRules as $rule) {
                    if (in_array($rule, $arrayRules) || in_array(explode(':', $rule)[0], $arrayRules)) {
                        $rules["$group.$field"][] = $rule;
                    } else {
                        $rules["$group.$field.*"][] = $rule;
                    }
                }
            } else {
                $rules["$group.$field"] = $fieldRules;
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            foreach ($this->duplicates as $duplicate) {

                $validator->errors()->add(
                    'supplies',
                    "El insumo {$duplicate} está repetido en la hoja Supplies."
                );
            }

            $rules = $this->dynamicRules();
            $supply_type = SupplyType::find($this->input('supply_type_id'));
            $models = collect($this->getModelTypes());

            foreach ($this->input('supplies', []) as $index => $supply) {

                $rowValidator = Valid::make(
                    $supply,
                    $rules,
                    $this->messages()
                );

                if ($rowValidator->fails()) {

                    foreach ($rowValidator->errors()->messages() as $field => $messages) {

                        foreach ($messages as $message) {

                            $validator->errors()->add(
                                "supplies.$index.$field",
                                $message
                            );
                        }
                    }
                }

                foreach ($supply_type?->settings?->form ?? [] as $column) {
                    $param = $models->firstWhere('model', $models->firstWhere('model', $column->model ?? null)['param'] ?? null);
                    $param_id = $param ? ($column->{$param['field']} ?? null) : null;

                    if (! $param_id) {
                        continue;
                    }

                    foreach (Arr::wrap(data_get($supply, "class.$column->field")) as $key => $id) {
                        $belongs = $column->model::find($id)
                            ?->{Str::snake(class_basename($param['model']))}()
                            ->where('model_has_subitems.subitem_id', $param_id)
                            ->exists();

                        if (! $belongs) {
                            $validator->errors()->add(
                                ($column->cardinality ?? 'single') === 'multiple' ? "supplies.$index.class.$column->field.$key" : "supplies.$index.class.$column->field",
                                'No pertenece al '.Str::lower($param['label']).' configurado.'
                            );
                        }
                    }
                }
            }
        });
    }
}
