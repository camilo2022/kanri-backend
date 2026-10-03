<?php

namespace App\Http\Requests\Trademark;

use App\Models\Group;
use App\Models\Trademark;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,'.Trademark::ITEM_ID],
            'group_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Group::ITEM_ID],
            'name' => ['required', 'string', 'min:4', 'max:50', 'uppercase', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Trademark::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'logo' => ['required', 'array'],
            'logo.file' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'cover' => ['nullable', 'array'],
            'cover.file' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un número.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'exists' => 'No está registrado.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'image' => 'Debe ser una imagen.',
            'mimes' => 'Extensión inválida. Permitidas :values.',
            'photo.max' => 'Se permite máximo :max KB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del area',
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
}
