<?php

namespace App\Http\Requests\OrganizationalStructure\Position;

use App\Models\Position;

use App\Models\Area;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PositionAllRequest extends FormRequest
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
        return ['organizational_structure.areas.positions.all', 'employees.store', 'employees.update'];
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Area::ITEM_ID],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Position::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean']
        ];
    }

    public function attributes(): array
    {
        return [
            'area_id' => 'Identificador del área',
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
            'with_trashed' => 'Registros inactivos'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'area_id' => $this->route('area_id'),
            'with_trashed' => $this->boolean('with_trashed'),
        ]);
    }
}
