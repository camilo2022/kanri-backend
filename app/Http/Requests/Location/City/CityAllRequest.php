<?php

namespace App\Http\Requests\Location\City;

use App\Models\City;
use App\Services\PermissionService;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CityAllRequest extends FormRequest
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
        return ['locations.countries.all', 'people.store', 'people.update'];
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(City::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function attributes(): array
    {
        return [
            'department_id' => 'Departamento',
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['department_id' => $this->route('department_id')]);
    }
}
