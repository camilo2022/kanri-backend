<?php

namespace App\Http\Requests\Typification\BootType;

use App\Models\BootType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BootTypeStoreRequest extends FormRequest
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
        return ['boot_types.store'];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . BootType::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'numeric', 'digits_between:2,5'],
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

            if (!$this->filled('settings.code')) {
                return;
            }

            $exists = BootType::withTrashed()
                ->where('settings->code', $this->input('settings.code'))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'settings.code',
                    'El código ya se encuentra registrado.'
                );
            }
        });
    }
}
