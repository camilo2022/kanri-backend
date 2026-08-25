<?php

namespace App\Http\Requests\Typification\SupplyType\Variant;

use App\Models\SupplyType;
use App\Models\Variant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator as Valid;

class VariantSaveRequest extends FormRequest
{

    public array $duplicates = [];

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
            'variants' => ['required', 'array'],
            'variants.*' => ['required', 'array'],
            'variants.*.name' => ['required', 'min:4', 'max:50'],
            'variants.*.description' => ['required', 'uppercase', 'string', 'max:255']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'uppercase' => 'Debe de ingresar mayúsculas.',
            'exists' => 'No registrado en el sistema.',
            'distinct' => 'El valor ya existe dentro del elemento.',
        ];
    }

    public function attributes(): array
    {
        return [
            'variants' => 'Variantes',
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

    protected  function dynamicRules(): array
    {
        $supply_type = SupplyType::find($this->input('supply_type_id'));

        $arrayRules = [
            'required',
            'nullable',
            'min',
            'max',
            'size'
        ];

        $rules = [];

        foreach ($supply_type?->settings?->form ?? [] as $column) {

            $field = $column->field ?? null;

            if (!$field) {
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
                    'variants',
                    "La variante {$duplicate} está repetida en la hoja Variantes."
                );
            }

            $rules = $this->dynamicRules();

            foreach ($this->input('variants', []) as $index => $variant) {

                $rowValidator = Valid::make(
                    $variant,
                    $rules,
                    $this->messages()
                );

                if ($rowValidator->fails()) {

                    foreach ($rowValidator->errors()->messages() as $field => $messages) {

                        foreach ($messages as $message) {

                            $validator->errors()->add(
                                "variants.$index.$field",
                                $message
                            );
                        }
                    }
                }
            }
        });
    }
}
