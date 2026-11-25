<?php

namespace App\Http\Requests\Bank;

use App\Models\Bank;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BankStoreRequest extends FormRequest
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
        return ['banks.store'];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,'.Bank::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
        ];
    }
}
