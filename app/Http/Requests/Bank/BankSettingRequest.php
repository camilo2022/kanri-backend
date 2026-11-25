<?php

namespace App\Http\Requests\Bank;

use App\Models\AccountType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BankSettingRequest extends FormRequest
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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['banks.settings'];
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.validations' => ['present', 'array'],
            'settings.validations.*' => ['required', 'array'],
            'settings.validations.*.account_type_id' => ['required', 'integer', 'distinct', 'exists:subitems,id,item_id,'.AccountType::ITEM_ID.',deleted_at,NULL'],
            'settings.validations.*.regex' => ['required', 'string', 'max:255'],
            'settings.validations.*.message' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach ($this->input('settings.validations', []) as $i => $v) {
            $attributes["settings.validations.$i.account_type_id"] = 'Tipo de cuenta';
            $attributes["settings.validations.$i.regex"] = 'Expresión regular';
            $attributes["settings.validations.$i.message"] = 'Mensaje';
        }

        return [
            'settings' => 'Configuraciones',
            'settings.validations' => 'Validaciones',
            ...$attributes,
        ];
    }

    public function withValidator($validator)
    {
        // Un regex mal escrito impediría registrar cualquier cuenta del banco: se prueba antes de guardarlo
        $validator->after(function ($validator) {
            foreach ($this->input('settings.validations', []) as $i => $v) {
                $regex = $v['regex'] ?? null;
                if (blank($regex)) continue;

                if (! is_string($regex) || @preg_match($regex, '') === false) {
                    $validator->errors()->add("settings.validations.$i.regex", 'Debe ser una expresión regular válida, con delimitadores (por ejemplo /^\d{11}$/).');
                }
            }
        });
    }

    protected function passedValidation()
    {
        // Cada fila es un tipo de cuenta que el banco maneja; se guarda solo lo que la regla necesita
        $validations = collect($this->input('settings.validations', []))
            ->map(fn ($v) => ['account_type_id' => (int) $v['account_type_id'], 'regex' => $v['regex'], 'message' => $v['message']])
            ->values()
            ->all();

        $this->merge(['settings' => ['validations' => $validations]]);
    }
}
