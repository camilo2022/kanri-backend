<?php

namespace App\Http\Requests\Workflow\Process;

use App\Models\Process;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessAllRequest extends FormRequest
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
            'process_id' => ['nullable', 'numeric', 'exists:subitems,id,item_id,' . Process::ITEM_ID],
            'in_technical_sheet' => ['nullable', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'boolean' => 'Deber ser verdadero o falso.',
            'in_technical_sheet' => 'Aplica a Ficha Técnica'
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por página.',
            'page' => 'N° de página.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
            'with_trashed' => 'Registros inactivos',
            'process_id' => 'Identificador del proceso.',
            'in_technical_sheet' => 'Aplica a Ficha Técnica'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
        if ($this->has('in_technical_sheet')) {
            $this->merge(['in_technical_sheet' => $this->boolean('in_technical_sheet')]);
        }
    }
}
