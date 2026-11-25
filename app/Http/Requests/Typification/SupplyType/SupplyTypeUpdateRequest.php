<?php

namespace App\Http\Requests\Typification\SupplyType;

use App\Models\SupplyType;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplyTypeUpdateRequest extends FormRequest
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
        return ['typification.supply_types.update'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,' . SupplyType::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . SupplyType::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255',],
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
            'id' => 'Identificador del tipo de insumo',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.has_supplies' => 'Tiene insumos',
            'settings.in_technical_sheet' => 'Aplica a Ficha Técnica',
            'settings.in_production_order' => 'Aplica a Orden de Producción',
            'settings.paragraph' => 'Apartado',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (empty($this->settings['in_production_order'])) {
                return;
            }

            $exist = SupplyType::where('id', '!=', $this->route('id'))
                ->get()
                ->first(function ($supply_type) {
                    $settings = $supply_type->settings;

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

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);

        // Un indicador que no llega (tipos sin configurar, por ejemplo los que trajo la migración) vale falso
        $this->merge(['settings' => array_merge((array) $this->input('settings', []), [
            'has_supplies' => $this->boolean('settings.has_supplies'),
            'in_technical_sheet' => $this->boolean('settings.in_technical_sheet'),
            'in_production_order' => $this->boolean('settings.in_production_order'),
        ])]);
    }

    protected function passedValidation()
    {
        // Editar cambia los indicadores; el formulario de los insumos lo escribe Configurar y aquí se conserva
        $form = data_get(SupplyType::find($this->route('id'))?->settings, 'form');

        $this->merge(['settings' => array_merge(
            collect($this->input('settings'))->only(['has_supplies', 'in_technical_sheet', 'in_production_order', 'paragraph'])->all(),
            $form !== null ? ['form' => $form] : []
        )]);
    }
}
