<?php

namespace App\Http\Requests\Typification\BloodType;

use App\Models\BloodType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BloodTypeStoreRequest extends FormRequest
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
        return ['blood_types.store'];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'regex:/^(A|B|AB|O)[+-]$/', 'string', 'min:2', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . BloodType::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255']
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => 'Formato Inválido. Es un tipo de sangre no válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }
}
