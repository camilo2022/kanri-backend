<?php

namespace App\Http\Requests\Typification\PersonType\DocumentType;

use App\Models\DocumentType;
use App\Models\PersonType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DocumentTypeStoreRequest extends FormRequest
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
        return ['typification.person_types.document_types.store'];
    }

    public function rules(): array
    {
        return [
            'person_type_id' => ['required', 'exists:subitems,id,item_id,'.PersonType::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,'.DocumentType::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'person_type_id' => 'Identificador del tipo de persona',
            'name' => 'Nombre',
            'description' => 'Descripción',
        ];
    }
}
