<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Services\PermissionService;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserAllRequest extends FormRequest
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
        return ['users.all'];
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(User::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean']
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Dirección de ordenamiento.',
            'with_trashed' => 'Registros inactivos.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
    }
}
