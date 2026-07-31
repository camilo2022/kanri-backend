<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Trademark;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductStoreRequest extends FormRequest
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
            'code' => ['required', 'unique:products,code'],
            'trademark_id' => ['required', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'subcategory_id' => ['required', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID],
            'reference_relation' => ['nullable', 'boolean'],
            'technical_sheet_id' =>  ['nullable', 'exists:technical_sheets,id']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'exists' => 'No registrado en el sistema.',
            'unique' => 'Ya está registrado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Código de la Referencia del Producto',
            'trademark_id' => 'Identificador de la marca',
            'subcategory_id' => 'Identificador de la subcategoría'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (filled($this->input('trademark_id')) && filled($this->input('code'))) {
                $trademark = Trademark::find($this->input('trademark_id'));
                $code = $this->input('code');
                $isValid = false;
                $messages = [];

                foreach ($trademark?->settings?->validations ?? [] as $item) {
                    if (preg_match($item->regex, $code)) {
                        $isValid = true;
                        break;
                    }
                    $messages[] = $item->message;
                }

                if (!$isValid) {
                    $validator->errors()->add(
                        'code',
                        "No cumple con ninguna de las validaciones:\n• " .
                            implode("\n• ", $messages)
                    );
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['reference_relation' => $this->boolean('reference_relation')]);
    }
}
