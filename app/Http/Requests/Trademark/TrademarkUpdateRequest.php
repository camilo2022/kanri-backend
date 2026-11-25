<?php

namespace App\Http\Requests\Trademark;

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

class TrademarkUpdateRequest extends FormRequest
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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['trademarks.update'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,'.Trademark::ITEM_ID],
            'group_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Group::ITEM_ID],
            'name' => ['required', 'string', 'min:4', 'max:50', 'uppercase', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Trademark::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'logo' => ['required', 'array'],
            'logo.file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width='.File::MAX_IMAGE_SIDE.',max_height='.File::MAX_IMAGE_SIDE],
            'cover' => ['nullable', 'array'],
            'cover.file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width='.File::MAX_IMAGE_SIDE.',max_height='.File::MAX_IMAGE_SIDE],
            'logo.photo_type_id' => ['required_with:logo.file', 'nullable', 'numeric', 'exists:subitems,id,item_id,' . FileType::ITEM_ID],
            'logo.photo_subtype_id' => ['required_with:logo.file', 'nullable', 'numeric', 'exists:subitems,id,item_id,' . FileSubtype::ITEM_ID],
            'cover.photo_type_id' => ['required_with:cover.file', 'nullable', 'numeric', 'exists:subitems,id,item_id,' . FileType::ITEM_ID],
            'cover.photo_subtype_id' => ['required_with:cover.file', 'nullable', 'numeric', 'exists:subitems,id,item_id,' . FileSubtype::ITEM_ID],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'string', 'max:5', Rule::unique('subitems', 'settings->code')->where('item_id', Trademark::ITEM_ID)->ignore($this->route('id'))],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la marca',
            'group_id' => 'Identificador del Grupo',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'logo' => 'Logo de la Marca',
            'cover' => 'Imagen de portada para el catálogo',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }

    protected function passedValidation()
    {
        // Editar cambia el código; las validaciones de la referencia las escribe Configurar y aquí se conservan
        $validations = data_get(Trademark::find($this->route('id'))?->settings, 'validations');

        $this->merge(['settings' => array_merge(
            ['code' => $this->input('settings.code')],
            $validations !== null ? ['validations' => $validations] : []
        )]);
    }
}
