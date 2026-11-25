<?php

namespace App\Http\Requests\OrganizationalStructure\Position;

use App\Models\Area;
use App\Models\Position;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PositionStoreRequest extends FormRequest
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
        return ['organizational_structure.areas.positions.store'];
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'exists:subitems,id,item_id,' . Area::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . Position::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255']
        ];
    }

    public function attributes(): array
    {
        return [
            'area_id' => 'Identificador del área',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }
}
