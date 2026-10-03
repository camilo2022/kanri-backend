<?php

namespace App\Http\Requests\GarmentType;

use App\Models\GarmentType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GarmentTypeStoreRequest extends FormRequest
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
            'name' => ['required', 'regex:/^[A-Z ]+$/', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . GarmentType::ITEM_ID],
            'description' => ['required', 'regex:/^[A-Z ]+$/', 'max:255'],
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
            'regex' => 'Formato Inválido. El campo debe estar en mayúsculas.',
            'digits_between' => 'Debe tener entre 2 y 5 dígitos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.code' => 'Código',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $exists = GarmentType::query()
                ->where('settings->code', $this->input('settings.code'))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'settings.code',
                    'El código ya se encuentra registrado.'
                );
            }
        });
    }
}
