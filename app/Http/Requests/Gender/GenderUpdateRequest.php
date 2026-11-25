<?php

namespace App\Http\Requests\Gender;

use App\Models\Gender;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GenderUpdateRequest extends FormRequest
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
        return ['genders.update'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . Gender::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:1', 'max:50', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . Gender::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255']
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del género',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
