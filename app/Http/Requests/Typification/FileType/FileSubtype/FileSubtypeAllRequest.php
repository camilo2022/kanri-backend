<?php

namespace App\Http\Requests\Typification\FileType\FileSubtype;

use App\Models\FileSubtype;

use App\Models\FileType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FileSubtypeAllRequest extends FormRequest
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
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(FileSubtype::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
            'file_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.FileType::ITEM_ID],
        ];
    }

    public function attributes(): array
    {
        return [
            'file_type_id' => 'Tipo de archivo',
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
            'file_type_id' => $this->route('file_type_id'),
            'with_trashed' => $this->boolean('with_trashed'),
        ]);
    }
}
