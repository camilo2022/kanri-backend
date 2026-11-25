<?php

namespace App\Http\Requests\Typification\SupplyType;

use App\Models\SupplyType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplyTypeStoreRequest extends FormRequest
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
        return ['typification.supply_types.store'];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . SupplyType::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.has_supplies' => ['required', 'boolean'],
            'settings.in_technical_sheet' => ['required', 'boolean'],
            'settings.in_production_order' => ['required', 'boolean'],
            'settings.paragraph' => ['nullable', 'required_if:settings.in_production_order,true', 'in:fabric,roll'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.has_supplies' => 'Tiene insumos',
            'settings.in_technical_sheet' => 'Aplica a Ficha Técnica',
            'settings.in_production_order' => 'Aplica a Orden de Producción',
            'settings.paragraph' => 'Apartado',
        ];
    }

    protected function prepareForValidation()
    {
        // Un indicador que no llega (tipos sin configurar, por ejemplo los que trajo la migración) vale falso
        $this->merge(['settings' => array_merge((array) $this->input('settings', []), [
            'has_supplies' => $this->boolean('settings.has_supplies'),
            'in_technical_sheet' => $this->boolean('settings.in_technical_sheet'),
            'in_production_order' => $this->boolean('settings.in_production_order'),
        ])]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (empty($this->settings['in_production_order'])) {
                return;
            }

            $exist = SupplyType::all()->first(function ($supplyType) {
                $settings = $supplyType->settings;

                return !empty($settings->in_production_order)
                    && $settings->paragraph === $this->settings['paragraph'];
            });

            if ($exist) {
                $validator->errors()->add(
                    'settings.paragraph',
                    "Ya existe un tipo de insumo configurado con este apartado. Solo puede existir uno por apartado."
                );
            }
        });
    }
}
