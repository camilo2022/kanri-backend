<?php

namespace App\Http\Requests\Typification\SupplyType;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplyTypeAllRequest extends FormRequest
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
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'numeric'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:id,name,description,created_at,updated_at,deleted_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
            'in_technical_sheet' => ['nullable', 'boolean'],
            'in_production_order' => ['nullable', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'boolean' => 'Deber ser verdadero o falso.'
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
            'in_technical_sheet' => 'Aplica a Ficha Técnica'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
        if ($this->has('in_technical_sheet')) {
            $this->merge(['in_technical_sheet' => $this->boolean('in_technical_sheet')]);
        }
        if ($this->has('in_production_order')) {
            $this->merge(['in_production_order' => $this->boolean('in_production_order')]);
        }
    }
}
