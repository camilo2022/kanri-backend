<?php

namespace App\Http\Requests\Typification\StopType\Stop;

use App\Models\Stop;

use App\Models\StopType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StopAllRequest extends FormRequest
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
        return ['stop_types.stops.all', 'productivity.workstation'];
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Stop::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
            'stop_type_id' => ['nullable', 'numeric', 'exists:subitems,id,item_id,'.StopType::ITEM_ID],
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
            'with_trashed' => 'Registros inactivos',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'stop_type_id' => $this->route('stop_type_id'),
            'with_trashed' => $this->boolean('with_trashed'),
        ]);
    }
}
