<?php

namespace App\Http\Requests\Navegation\Module;

use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ModuleUpdateRequest extends FormRequest
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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['navegation.modules.update'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:modules,id'],
            'name' => ['required', 'string', 'max:18', 'unique:modules,name,' . $this->route('id') . ',id', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\.?\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/'],
            'icon' => ['required', 'string', 'max:40', 'regex:/^Fa([A-Z][a-zA-Z0-9]*|[0-9][a-zA-Z0-9]*)$/']
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => 'Formato inválido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre del módulo',
            'icon' => 'Icono del módulo',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
