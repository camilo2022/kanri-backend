<?php

namespace App\Http\Requests\Trademark;

use App\Models\Category;
use App\Models\File;
use App\Models\FileSubtype;
use App\Models\FileType;
use App\Models\Group;
use App\Models\Trademark;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TrademarkStoreRequest extends FormRequest
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
        return ['trademarks.store'];
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Group::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . Trademark::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'logo' => ['required', 'array'],
            'logo.file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width='.File::MAX_IMAGE_SIDE.',max_height='.File::MAX_IMAGE_SIDE],
            'cover' => ['nullable', 'array'],
            'cover.file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width='.File::MAX_IMAGE_SIDE.',max_height='.File::MAX_IMAGE_SIDE],
            'logo.photo_type_id' => ['required_with:logo.file', 'nullable', 'numeric', 'exists:subitems,id,item_id,' . FileType::ITEM_ID],
            'logo.photo_subtype_id' => ['required_with:logo.file', 'nullable', 'numeric', 'exists:subitems,id,item_id,' . FileSubtype::ITEM_ID],
            'cover.photo_type_id' => ['required_with:cover.file', 'nullable', 'numeric', 'exists:subitems,id,item_id,' . FileType::ITEM_ID],
            'cover.photo_subtype_id' => ['required_with:cover.file', 'nullable', 'numeric', 'exists:subitems,id,item_id,' . FileSubtype::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'string', 'max:5', Rule::unique('subitems', 'settings->code')->where('item_id', Trademark::ITEM_ID)],
        ];
    }

    public function attributes(): array
    {
        return [
            'group_id' => 'Grupo',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'logo' => 'Logo de la Marca',
            'cover' => 'Imagen de portada para el catálogo'
        ];
    }
}
