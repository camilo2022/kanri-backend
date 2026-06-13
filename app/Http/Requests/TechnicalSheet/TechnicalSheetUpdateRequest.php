<?php

namespace App\Http\Requests\TechnicalSheet;

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Collection;
use App\Models\Color;
use App\Models\GarmentType;
use App\Models\Product;
use App\Models\Subgroup;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TechnicalSheetUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:technical_sheets,id'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:now'],
            'product_id' => ['required', 'exists:products,id', 'unique:technical_sheets,product_id,' . $this->route('id') . ',id'],
            'collection_id' => ['required', 'exists:subitems,id,item_id,' . Collection::ITEM_ID . ',deleted_at,NULL'],
            'garment_type_id' => ['required', 'exists:subitems,id,item_id,' . GarmentType::ITEM_ID . ',deleted_at,NULL'],
            'subgroup_id' => ['required', 'exists:subitems,id,item_id,' . Subgroup::ITEM_ID . ',deleted_at,NULL'],
            'wash_tone_id' => ['required', 'exists:subitems,id,item_id,' . WashTone::ITEM_ID . ',deleted_at,NULL'],
            'color_id' => ['required', 'exists:subitems,id,item_id,' . Color::ITEM_ID . ',deleted_at,NULL'],
            'waistband_type_id' => ['required', 'exists:subitems,id,item_id,' . WaistbandType::ITEM_ID . ',deleted_at,NULL'],
            'measure_of_waistband' => ['required', 'numeric', 'min:1'],
            'boot_type_id' => ['required', 'exists:subitems,id,item_id,' . BootType::ITEM_ID . ',deleted_at,NULL'],
            'yoke_type_id' => ['required', 'exists:subitems,id,item_id,' . YokeType::ITEM_ID . ',deleted_at,NULL'],
            'back_type_id' => ['required', 'exists:subitems,id,item_id,' . BackType::ITEM_ID . ',deleted_at,NULL'],
            'pattern_maker_id' => ['required',  'exists:employees,id,deleted_at,NULL'],
            'physical_sample' => ['required', 'boolean'],
            'number_of_buttons' => ['required', 'numeric', 'min:1'],
            'observation' =>  ['nullable', 'string', 'max:1000'],
            'description' =>  ['nullable', 'string', 'max:1000'],
            'photo_d' => ['required', 'array'],
            'photo_d.file' => ['sometimes', 'image', 'max:2048'],
            'photo_t' => ['required', 'array'],
            'photo_t.file' => ['sometimes', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'date_format' => 'Formato inválido. Permitido :format.',
            'date.before_or_equal' => 'No puede ser posterior a ' . now()->format('Y-d-m') . '.',
            'exists' => 'No registrado en el sistema.',
            'unique' => 'Ya está registrado.',
            'boolean' => 'Debe ser un dato tipo si/no',
            'numeric' => 'Debe ser un dato númerico',
            'min' => 'El valor debe ser mayor o igual a :min',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'array' => 'Debe ser un arreglo de datos',
            'image' => 'Debe ser una imagen.',
            'mimes' => 'Extensión inválida. Permitidas :values.',
            'photo.max' => 'Se permite máximo :max KB.'
        ];
    }

    public function attributes(): array
    {
        return [
            'date' => 'Fecha de aporbación del producto',
            'product_id' => 'Identificador del producto',
            'collection_id' => 'Identificador de la colección',
            'garment_type_id' => 'Identificador del tipo de prenda',
            'subgroup_id' => 'Identificador del subgrupo',
            'wash_tone_id' => 'Identificador del tono de lavado',
            'color_id' => 'Identificador del color',
            'waistband_type_id' => 'Identificador del tipo de pretina',
            'measure_of_waistband' => 'Medida de la pretina',
            'boot_type_id' => 'Identificador del tipo de bota',
            'yoke_type_id' => 'Identificador del tipo de cotilla',
            'back_type_id' => 'Identificador del tipo de trasero',
            'pattern_maker_id' => 'Identificador del patronista',
            'physical_sample' => 'Indicador de muestra física',
            'number_of_buttons' => 'Número de botones',
            'observation' =>  'Observación',
            'description' =>  'Descripción',
            'photo_d' => 'Foto delantera',
            'photo_t' => 'Foto trasera'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $product = Product::find($this->integer('product_id'));

            $aux = [
                'D' => 'photo_d.file',
                'T' => 'photo_t.file',
            ];

            foreach ($aux as $suffix => $field) {
                $photo = $this->file($field);

                if ($this->hasFile($field)) {
                    $expectedName = $product->code . '-' . $suffix;

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

    protected function prepareForValidation()
    {
        $this->merge(['physical_sample' => $this->boolean('physical_sample')]);
        $this->merge(['id' => $this->route('id')]);
    }
}
