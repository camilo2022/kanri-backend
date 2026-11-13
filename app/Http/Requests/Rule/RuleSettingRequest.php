<?php

namespace App\Http\Requests\Rule;

use App\Models\Rule;
use App\Traits\ModelTypes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RuleSettingRequest extends FormRequest
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
        return [
            'id' => ['required', 'in:'.Rule::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.weights' => ['required', 'array'],
            'settings.weights.*' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.in' => 'Solo se configura el ítem de reglas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del ítem',
            'settings' => 'Configuraciones',
        ];
    }

    public function withValidator($validator)
    {
        // Los pesos solo pueden ser de los maestros que comparan las reglas (el alcance proceso/subproceso/operación no pesa)
        $validator->after(function ($validator) {
            $criteria = array_diff(array_keys($this->getModelTypesFromRules()), ['process', 'subprocess', 'operation']);

            foreach (array_keys((array) $this->input('settings.weights', [])) as $key) {
                if (! in_array($key, $criteria, true)) {
                    $validator->errors()->add("settings.weights.$key", 'No es un criterio de las reglas.');
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
