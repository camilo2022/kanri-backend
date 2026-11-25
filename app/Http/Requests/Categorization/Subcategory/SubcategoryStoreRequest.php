<?php

namespace App\Http\Requests\Categorization\Subcategory;

use App\Models\Category;
use App\Models\Subcategory;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubcategoryStoreRequest extends FormRequest
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
        return ['categorization.categories.subcategories.store'];
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:subitems,id,item_id,' . Category::ITEM_ID . ',deleted_at,NULL'],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . Subcategory::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255']
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'Categoría',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }
}
