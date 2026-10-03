<?php

namespace App\Http\Requests\Typification\SupplierType;

use App\Models\SupplierType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplierTypeUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:subitems,id,item_id,'.SupplierType::ITEM_ID],
            'name' => ['required', 'string', 'min:4', 'max:50', 'uppercase', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.SupplierType::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.has_person' => ['required', 'boolean'],
            'settings.has_account_bank' => ['required', 'boolean'],
            'settings.in_production_order' => ['required', 'boolean'],
            'settings.paragraph' => ['nullable', 'required_if:settings.in_production_order,true', 'in:production'],
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
            'digits_between' => 'Debe tener entre 2 y 5 dígitos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.has_person' => 'Indicador de datos de persona',
            'settings.has_account_bank' => 'Indicador de datos de bancarios',
            'settings.in_production_order' => 'Indicador de orden de producción',
            'settings.paragraph' => 'Indicador de apartado al que pertenece en la orden de producción',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty($this->settings['in_production_order'])) {
                return;
            }

            $exist = SupplierType::where('id', '!=', $this->route('id'))
                ->get()
                ->first(function ($supplie_type) {
                    $settings = $supplie_type->settings;

                    return ! empty($settings->in_production_order)
                        && $settings->paragraph === $this->settings['paragraph'];
                });

            if ($exist) {
                $validator->errors()->add(
                    'paragraph',
                    'Ya existe un tipo de insumo configurado con este apartado. Solo puede existir uno por apartado.'
                );
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
