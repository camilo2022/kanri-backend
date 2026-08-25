<?php

namespace App\Http\Requests\Management\TechnicalSheet;

use App\Http\Controllers\SupplyTypeController;
use App\Models\BackType;
use App\Models\BootType;
use App\Models\Collection;
use App\Models\Color;
use App\Models\GarmentType;
use App\Models\Process;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Subitem;
use App\Models\SupplyType;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Models\Trademark;
use App\Models\Variant;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TechnicalSheetSaveRequest extends FormRequest
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
            'id' => ['sometimes', 'exists:technical_sheets,id'],
            //'code' => ['required', 'unique:technical_sheets,code,' . $this->input('id') . ',id'],
            'collection_id' => ['required', 'exists:subitems,id,item_id,' . Collection::ITEM_ID],
            'garment_type_id' => ['required', 'exists:subitems,id,item_id,' . GarmentType::ITEM_ID],
            'wash_tone_id' => ['required', 'exists:subitems,id,item_id,' . WashTone::ITEM_ID],
            'boot_type_id' => ['required', 'exists:subitems,id,item_id,' . BootType::ITEM_ID],
            'observation' =>  ['sometimes', 'string', 'max:255'],
            'status' =>  ['required', 'in:' . implode(',', [TechnicalSheet::PENDING, TechnicalSheet::REVIEW, TechnicalSheet::APPROVED, TechnicalSheet::CANCELLED])],
            'product' => ['required', 'array'],
            'product.id' => ['sometimes', 'exists:products,id'],
            'product.code' => ['required', 'unique:products,code,' . $this->input('product.id') . ',id'],
            'product.trademark_id' => ['required', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'product.subcategory_id'  => ['required', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID],
            'technical_sheet_details' => ['required', 'array'],
            'technical_sheet_details.*' => ['required', 'array'],
            'technical_sheet_details.*.id' => ['sometimes', 'exists:technical_sheet_details,id'],
            'technical_sheet_details.*.model_type' => ['required'],
            'technical_sheet_details.*.model_id' => ['required', 'exists:subitems,id'],
            'technical_sheet_details.*.status' => ['required', 'in:' . implode(',', [TechnicalSheetDetail::PENDING, TechnicalSheetDetail::REVIEW, TechnicalSheetDetail::APPROVED])],
            'variants' => ['required', 'array'],
            'variants.*' => ['sometimes', 'numeric', 'exists:subitems,id,item_id,' . Variant::ITEM_ID],
            'photo_d' => ['required', 'array'],
            'photo_d.file' => ['sometimes', 'image', 'max:2048'],
            'photo_t' => ['required', 'array'],
            'photo_t.file' => ['sometimes', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => "Es obligatorio.\n",
            'exists' => 'No registrado en el sistema.',
            'unique' => 'El registro debe ser unico.',
            'string' => 'Debe ser una cadena de caracteres.',
            'max' => 'No debe exceder los :max caracteres.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'array' => 'Debe ser una lista de datos.',
            'boolean' => 'Debe se un valor falso/verdadero',
            'numeric' => 'Debe ser un valor numérico.',
            'image' => 'Debe ser una imagen.',
            'photo_d.max' => 'Se permite máximo :max KB.',
            'photo_t.max' => 'Se permite máximo :max KB.'
        ];
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->input('technical_sheet_details', []) as $i => $v) {
            $n = $i + 1;
            $base = "technical_sheet_details.$v[model_id]";
            $attributes["$base.id"] = "Id (Detalle n° $n)";
            $attributes["$base.model_type"] = "Modelo (Detalle n° $n)";
            $attributes["$base.model_id"] = "Identificador del model (Detalle n° $n)";
            $attributes["$base.status"] = "Estado (Detalle n° $n)";
        }

        foreach ($this->input('variants', []) as $i => $v) {
            $n = $i + 1;
            $base = "variants.$i";
            $attributes["$base.id"] = "Id (Variante n° $n)";
        }

        return [
            'id' => 'Identificador de la ficha técnica',
            'code' => 'Código de la ficha técnica',
            'collection_id' => 'Identificador de la colección',
            'garment_type_id' => 'Identificador del tipo de prendas',
            'wash_tone_id' => 'Identificador del tono de lavado',
            'boot_type_id' => 'Identificador del tipo de bota',
            'observation' =>  'Observación',
            'status' =>  'Estado de la ficha técnica',
            'product' => 'Producto asociado',
            'product.id' => 'Identificador del producto',
            'product.code' => 'Referencia del producto',
            'product.trademark_id' => 'Identificador de la marca del producto',
            'product.subcategory_id'  => 'Identificador de la subcategoría del producto',
            ...$attributes,
            'photo_d' => 'Foto delantera',
            'photo_t' => 'Foto trasera'

        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $trademark = Trademark::find($this->input('product.trademark_id'));
            $code = $this->input('product.code');
            $isValid = false;
            $messages = [];

            foreach ($trademark?->settings?->validations ?? [] as $item) {
                if (preg_match($item->regex, $code)) {
                    $isValid = true;
                    break;
                }
                $messages[] = $item->message;
            }

            if (!$isValid) {
                $validator->errors()->add(
                    'product.code',
                    "El código del producto no cumple las siguientes validaciones:\n\n• " .
                        implode("\n• ", $messages)
                );
            }

            $supply_types = SupplyType::with('variants')
                ->where('settings->in_technical_sheet', true)->get();

            foreach ($supply_types as $supply_type) {
                $has_variants = $supply_type->variants
                    ->pluck('id')
                    ->intersect($this->input('variants', []))
                    ->isNotEmpty();

                if (!$has_variants) {
                    $validator->errors()->add("variant.$supply_type->id", 'Debe seleccionar una variante');
                }
            }

            $supply_type_ids = [];

            foreach ($this->input('variants', []) as $key => $variant_id) {
                $variant = Variant::with('supply_type')->find($variant_id);

                if (in_array($variant?->supply_type->first()?->id, $supply_type_ids)) {
                    $validator->errors()->add("variants.$key", 'La variante pertenece a un tipo de insumo ya registrado.');
                }
            }

            $aux = [
                'D' => 'photo_d.file',
                'T' => 'photo_t.file',
            ];

            foreach ($aux as $suffix => $field) {
                $photo = $this->file($field);

                if ($this->hasFile($field) && $this->filled('product.code')) {
                    $expectedName = $this->input('product.code') . '-' . $suffix;

                    $filename = pathinfo(
                        $photo->getClientOriginalName(),
                        PATHINFO_FILENAME
                    );

                    if ($filename !== $expectedName) {
                        $validator->errors()->add(
                            $field,
                            "El nombre del archivo debe contener el código de referencia seguido del sufijo {$suffix}."
                        );
                    }
                }
            }
        });
    }
}
