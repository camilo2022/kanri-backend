<?php

namespace App\Http\Requests\Typification\Variant;

use App\Models\SupplyType;
use App\Models\Variant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator as Valid;

class VariantStoreRequest extends FormRequest
{
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
            'supply_type_id' => ['required', 'exists:subitems,id,item_id,' . SupplyType::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . Variant::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
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
            'uppercase' => 'Debe de ingresar mayúsculas.'
        ];
    }

    public function attributes(): array
    {
        return [
            'supply_type_id' => 'Identificador del tipo de insumo',
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
            $supply_type = SupplyType::find($this->input('supply_type_id'));
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
        });
    }
}
