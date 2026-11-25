<?php

namespace App\Http\Requests\Trademark;

use App\Models\Size;
use App\Models\Trademark;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TrademarkSizeRemoveRequest extends FormRequest
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
        return ['trademarks.size.remove'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'size_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Size::ITEM_ID, 'exists:model_has_subitems,model_id,model_type,' . Size::class . ',subitem_id,' . $this->route('id')]
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la marca',
            'size_id' => 'Identificador de la talla'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
