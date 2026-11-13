<?php

namespace App\Http\Requests\Rule;

use App\Models\Rule;
use App\Services\StandardRuleService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule as ValidationRule;

class RuleRestoreRequest extends FormRequest
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
            'id' => ['required', ValidationRule::exists('subitems', 'id')->where('item_id', Rule::ITEM_ID)->whereNotNull('deleted_at')],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la regla',
        ];
    }

    public function withValidator($validator)
    {
        // Al activarla no puede repetir la combinación de relaciones de otra regla activa
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = app(StandardRuleService::class);

            if ($service->activeSignatures()->contains($service->signatureOf((int) $this->input('id')))) {
                $validator->errors()->add('id', 'Ya existe una regla activa con esta misma combinación de relaciones.');
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
