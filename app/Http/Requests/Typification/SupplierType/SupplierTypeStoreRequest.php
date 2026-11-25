<?php

namespace App\Http\Requests\Typification\SupplierType;

use App\Models\SupplierType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplierTypeStoreRequest extends FormRequest
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
        return ['typification.supplier_types.store'];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,'.SupplierType::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.has_person' => ['required', 'boolean'],
            'settings.has_account_bank' => ['required', 'boolean'],
            'settings.in_production_order' => ['required', 'boolean'],
            'settings.paragraph' => ['required_if:settings.in_production_order,true', 'in:production'],
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

            $exist = SupplierType::all()->first(function ($supplier_type) {
                $settings = $supplier_type->settings;

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
}
