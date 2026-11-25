<?php

namespace App\Http\Requests\Categorization\Subcategory;

use App\Models\Subcategory;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SubcategoryRestoreRequest extends FormRequest
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
        return ['categorization.categories.subcategories.restore'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', Rule::exists('subitems', 'id')->where('item_id', Subcategory::ITEM_ID)->whereNotNull('deleted_at')]
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la subcategoría'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
