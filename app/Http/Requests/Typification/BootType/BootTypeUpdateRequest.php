<?php

namespace App\Http\Requests\Typification\BootType;

use App\Models\BootType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BootTypeUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:subitems,id,item_id,' . BootType::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . BootType::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'numeric', 'digits_between:2,5'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'exists' => 'No está registrado.',
            'uppercase' => 'Debe de ingresar mayúsculas.',
            'digits_between' => 'Debe tener entre 2 y 5 dígitos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del tipo de bota',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.code' => 'Código',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (!$this->filled('settings.code')) {
                return;
            }

            $exists = BootType::query()
                ->where('settings->code', $this->input('settings.code'))
                ->whereKeyNot($this->route('id'))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'settings.code',
                    'El código ya se encuentra registrado.'
                );
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
