<?php

namespace App\Http\Requests\Trademark;

use App\Models\Size;
use App\Models\Trademark;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TrademarkSizeAssignRequest extends FormRequest
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
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'size_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Size::ITEM_ID, 'unique:model_has_subitems,subitem_id,NULL,NULL,model_type,' . Trademark::class . ',model_id,' . $this->route('id')]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'numeric' => 'Debe ser un número.',
            'exists' => 'No hay ningún registro.',
            'unique' => 'Ya registrado.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la marca',
            'subcategory_id' => 'Identificador de la talla'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
