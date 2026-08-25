<?php

namespace App\Http\Requests\TechnicalSheet\Transformation;

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Collection;
use App\Models\Color;
use App\Models\GarmentType;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\SupplyType;
use App\Models\TechnicalSheet;
use App\Models\Trademark;
use App\Models\Variant;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator as Valid;
use Override;

class TransformationStoreRequest extends FormRequest
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
        logger($this);
        return [
            'technical_sheet_id' => ['required', 'exists:technical_sheets,id'],
            'reasigned_curve' => ['nullable', 'boolean'],
            'production_order_id' => ['required_if:reasigned_curve,true'],
            'product' => ['required', 'array'],
            'product.code' => ['required', 'string', 'unique:products,code'],
            'product.trademark_id' => ['required', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'product.subcategory_id' => ['required', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID],
            'product_stara' => ['nullable', 'array'],
            'product_stara.code' => ['required_with:product_stara', 'string', 'unique:products,code'],
            'product_stara.trademark_id' => ['required_with:product_stara', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'product_stara.subcategory_id' => ['required_with:product_stara', 'same:product.subcategory_id', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID],
            'technical_sheet' => ['required', 'array'],
            'technical_sheet.date' => ['required', 'date_format:Y-m-d', 'before_or_equal:now'],
            'technical_sheet.status' =>  ['required', 'in:' . implode(',', [TechnicalSheet::PENDING, TechnicalSheet::REVIEW, TechnicalSheet::APPROVED, TechnicalSheet::CANCELLED])],
            'technical_sheet.code' => ['required', 'unique:technical_sheets,code,' . $this->input('id') . ',id'],
            'technical_sheet.collection_id' => ['required', 'exists:subitems,id,item_id,' . Collection::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.garment_type_id' => ['required', 'exists:subitems,id,item_id,' . GarmentType::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.subgroup_id' => ['required', 'exists:subitems,id,item_id,' . Subgroup::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.wash_tone_id' => ['required', 'exists:subitems,id,item_id,' . WashTone::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.color_id' => ['required', 'exists:subitems,id,item_id,' . Color::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.waistband_type_id' => ['required', 'exists:subitems,id,item_id,' . WaistbandType::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.measure_of_waistband' => ['required', 'numeric', 'min:1'],
            'technical_sheet.boot_type_id' => ['required', 'exists:subitems,id,item_id,' . BootType::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.yoke_type_id' => ['required', 'exists:subitems,id,item_id,' . YokeType::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.back_type_id' => ['required', 'exists:subitems,id,item_id,' . BackType::ITEM_ID . ',deleted_at,NULL'],
            'technical_sheet.pattern_maker_id' => ['required',  'exists:employees,id,deleted_at,NULL'],
            'technical_sheet.physical_sample' => ['required', 'boolean'],
            'technical_sheet.number_of_buttons' => ['required', 'numeric', 'min:1'],
            'technical_sheet.observation' =>  ['nullable', 'string', 'max:1000'],
            'technical_sheet.description' =>  ['nullable', 'string', 'max:1000'],
            'technical_sheet.photo_d' => ['required', 'array'],
            'technical_sheet.photo_d.file' => ['sometimes', 'image', 'max:2048'],
            'technical_sheet.photo_t' => ['required', 'array'],
            'technical_sheet.photo_t.file' => ['sometimes', 'image', 'max:2048'],
            'technical_sheet.variants' => ['required', 'array'],
            'technical_sheet.technical_sheet_details' => ['required', 'array'],
            'technical_sheet.technical_sheet_details.*' => ['sometimes', 'array'],
            'technical_sheet.technical_sheet_details.*.settings' => ['sometimes', 'array'],
            'technical_sheet.technical_sheet_details.*.settings.dinamic' => ['sometimes', 'array'],
            'technical_sheet.technical_sheet_details.*.settings.dinamic.values' => ['sometimes', 'array'],
            'technical_sheet.technical_sheet_details.*.settings.dinamic.insert_values' => ['required_with:technical_sheet_details.*.settings.dinamic', 'boolean'],
            'technical_sheet.technical_sheet_details.*.settings.static' => ['sometimes', 'array'],
            'technical_sheet.technical_sheet_details.*.settings.static.values' => ['sometimes', 'array'],
            'technical_sheet.technical_sheet_details.*.settings.static.insert_values' => ['required_with:technical_sheet_details.*.settings.static', 'boolean'],
            'production_order.cut' => ['required', 'string', 'uppercase', 'max:2'],
            'production_order.status' =>  ['required', 'in:' . implode(',', [ProductionOrder::PENDING, ProductionOrder::APPROVED, ProductionOrder::CANCELLED])],
            'production_order.production_order_details' => ['required', 'array', 'min:1', 'max:5'],
            'production_order.production_order_details.*.model_type' => ['required',  'in:' . Product::class],
            'production_order.production_order_details.*.model_id' => ['nullable', 'numeric'],
            'production_order.production_order_details.*.destination' => ['nullable', 'string', 'in:NACIONAL,MEDELLIN,STARA'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'exists' => 'No registrado en el sistema.',
            'unique' => 'Ya está registrado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product.code' => 'Código de la Referencia del Producto',
            'product.trademark_id' => 'Marca',
            'product.subcategory_id' => 'Subcategoría',
            'technical_sheet.date' => 'Fecha de aporbación del producto',
            'technical_sheet.collection_id' => 'Identificador de la colección',
            'technical_sheet.garment_type_id' => 'Identificador del tipo de prenda',
            'technical_sheet.subgroup_id' => 'Identificador del subgrupo',
            'technical_sheet.wash_tone_id' => 'Identificador del tono de lavado',
            'technical_sheet.color_id' => 'Identificador del color',
            'technical_sheet.waistband_type_id' => 'Identificador del tipo de pretina',
            'technical_sheet.measure_of_waistband' => 'Medida de la pretina',
            'technical_sheet.boot_type_id' => 'Identificador del tipo de bota',
            'technical_sheet.yoke_type_id' => 'Identificador del tipo de cotilla',
            'technical_sheet.back_type_id' => 'Identificador del tipo de trasero',
            'technical_sheet.pattern_maker_id' => 'Identificador del patronista',
            'technical_sheet.physical_sample' => 'Indicador de muestra física',
            'technical_sheet.number_of_buttons' => 'Número de botones',
            'technical_sheet.observation' =>  'Observación',
            'technical_sheet.description' =>  'Descripción',
            'technical_sheet.photo_d' => 'Foto delantera',
            'technical_sheet.photo_t' => 'Foto trasera'
        ];
    }

    protected function resolveRule(string $rule): string
    {
        if (!is_string($rule)) {
            return $rule;
        }

        preg_match_all(
            '/([A-Za-z\\\\]+)::([A-Z_]+)/',
            $rule,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {

            $fullConstant = "{$match[1]}::{$match[2]}";

            if (defined($fullConstant)) {

                $rule = str_replace(
                    $fullConstant,
                    constant($fullConstant),
                    $rule
                );
            }
        }

        return $rule;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $trademark_id = $this->input('product.trademark_id');
            $code = $this->input('product.code');

            if (filled($trademark_id) && filled($code)) {
                $trademark = Trademark::find($trademark_id);

                $is_valid = false;
                $messages = [];

                foreach ($trademark?->settings?->validations ?? [] as $item) {
                    if (preg_match($item->regex, $code)) {
                        $is_valid = true;
                        break;
                    }

                    $messages[] = $item->message;
                }

                if (!$is_valid) {
                    $validator->errors()->add(
                        'product.code',
                        "No cumple con ninguna de las validaciones:\n• " .
                            implode("\n• ", $messages)
                    );
                }
            }

            $product_stara = $this->input('product_stara');
            logger($product_stara);

            if (!empty($product_stara)) {

                if (
                    Product::where('code', $product_stara['code'] ?? null)->exists()
                ) {
                    logger('Ingreso');
                    $validator->errors()->add(
                        "production_order_details..Product.STARA.model_id",
                        'El código del producto STARA ya existe.'
                    );
                }
            }

            if (!empty($product_stara)) {
                if (
                    isset($code, $product_stara['code']) &&
                    $code === $product_stara['code']
                ) {
                    $validator->errors()->add(
                        "production_order_details." . Product::class . ".STARA.model_id",
                        'El código del producto STARA no puede ser igual al código del producto principal.'
                    );
                }

                if (
                    Product::where('code', $product_stara['code'] ?? null)->exists()
                ) {
                    $validator->errors()->add(
                        "production_order_details." . Product::class . ".STARA.model_id",
                        'El código del producto STARA ya existe.'
                    );
                }
            }

            $supply_types = SupplyType::with('variants')->where('settings->in_technical_sheet', true)->get();

            foreach ($supply_types as $supply_type) {
                $has_variants = $supply_type->variants
                    ->pluck('id')
                    ->intersect($this->input('technical_sheet.variants', []))
                    ->isNotEmpty();

                if (!$has_variants) {
                    $validator->errors()->add("variant.$supply_type->id", 'Debe seleccionar una variante');
                }
            }

            $supply_type_ids = [];

            foreach ($this->input('technical_sheet.variants', []) as $key => $variant_id) {
                $variant = Variant::with('supply_type')->find($variant_id);

                if (in_array($variant?->supply_type->first()?->id, $supply_type_ids)) {
                    $validator->errors()->add("variants.$key", 'La variante pertenece a un tipo de insumo ya registrado.');
                }
            }

            $aux = [
                'D' => 'technical_sheet.photo_d.file',
                'T' => 'technical_sheet.photo_t.file',
            ];

            foreach ($aux as $suffix => $field) {
                $photo = $this->file($field);

                if ($this->hasFile($field)) {
                    $expectedName = $code . '-' . $suffix;

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

            foreach ($this->input('technical_sheet.technical_sheet_details', []) as $detail) {

                $settings = $detail['settings'] ?? [];
                $id = $detail['model_id'] ?? null;

                if (!isset($settings['dinamic'])) {
                    continue;
                }

                $body = $settings['dinamic']['body'] ?? [];
                $values = $settings['dinamic']['values'] ?? [];
                $insertValues = $settings['dinamic']['insert_values'] ??  false;

                if ($insertValues && empty($values)) {
                    $validator->errors()->add(
                        "technical_sheet_details.$id.settings.dinamic.values",
                        'Si la tabla va a recibir informacion, debe agregar al menos una fila.'
                    );
                }

                foreach ($values as $in => $row) {

                    $rules = [];
                    $uniqueFields = [];

                    foreach ($body as $column) {

                        $field = $column['field'] ?? null;

                        if (!$field) {
                            continue;
                        }

                        $fieldRules = [];


                        foreach ($column['rules'] ?? [] as $rule) {

                            $rule = $this->resolveRule($rule);

                            if (is_string($rule) && str_starts_with($rule, 'unique')) {

                                $uniqueFields[] = $field;
                                continue;
                            }

                            $fieldRules[] = $rule;
                        }

                        $rules[$field] = $fieldRules;
                    }

                    $rules = collect($rules)
                        ->map(function ($fieldRules) {
                            return collect($fieldRules)
                                ->filter()
                                ->values()
                                ->map(function ($rule) {
                                    $rule = is_string($rule) ? $this->resolveRule($rule) : $rule;

                                    return $rule;
                                })
                                ->filter()
                                ->values()
                                ->toArray();
                        })
                        ->toArray();

                    $rowValidator = Valid::make(
                        $row,
                        $rules,
                        $this->messages()
                    );

                    if ($rowValidator->fails()) {

                        foreach ($rowValidator->errors()->messages() as $field => $messages) {

                            foreach ($messages as $message) {
                                if (isset($row['id'])) {
                                    $id_row = $row['id'];
                                    $validator->errors()->add(
                                        "technical_sheet_details.$id.settings.dinamic.values.$id_row.$field",
                                        $message
                                    );
                                } else {
                                    $validator->errors()->add(
                                        "technical_sheet_details.$id.settings.dinamic.values.$in.$field",
                                        $message
                                    );
                                }
                            }
                        }
                    }

                    foreach ($uniqueFields as $field) {

                        $seen = [];

                        foreach ($values as $in => $row) {

                            if (!array_key_exists($field, $row)) {
                                continue;
                            }

                            $value = $row[$field];

                            if ($value === null) {
                                continue;
                            }

                            if (isset($seen[$value])) {

                                if (isset($row['id'])) {
                                    $id_row = $row['id'];
                                    $validator->errors()->add(
                                        "technical_sheet_details.$id.settings.dinamic.values.$id_row.$field",
                                        "El valor ya existe dentro de esta tabla."
                                    );
                                } else {
                                    $validator->errors()->add(
                                        "technical_sheet_details.$id.settings.dinamic.values.$in.$field",
                                        "El valor ya existe dentro de esta tabla."
                                    );
                                }

                                continue;
                            }

                            $seen[$value] = true;
                        }
                    }
                }

                if (!isset($settings['static'])) {
                    continue;
                }

                $body = $settings['static']['body'] ?? [];
                $values = $settings['static']['values'] ?? [];
                $insertValues = $settings['static']['insert_values'] ?? false;

                if ($insertValues && empty($values)) {
                    $validator->errors()->add(
                        "technical_sheet_details.$id.settings.static.values",
                        'Si la tabla va a recibir informacion, debe rellenar los campos'
                    );
                }

                if (empty($values)) {
                    continue;
                }

                $rules = [];
                $uniqueFields = [];

                foreach ($body as $column) {

                    foreach ($column as $cell) {

                        if ($cell['cell'] === 'th') {
                            continue;
                        }

                        $field = $cell['field'] ?? null;

                        if (!$field) {
                            continue;
                        }

                        $fieldRules = [];

                        foreach ($cell['rules'] ?? [] as $rule) {

                            $rule = $this->resolveRule($rule);

                            if (is_string($rule) && str_starts_with($rule, 'unique')) {
                                $uniqueFields[] = $field;
                                continue;
                            }

                            $fieldRules[] = $rule;
                        }

                        $rules[$field] = $fieldRules;
                    }
                }

                $rules = collect($rules)
                    ->map(function ($fieldRules) {
                        return collect($fieldRules)
                            ->map(
                                fn($rule) =>
                                is_string($rule)
                                    ? $this->resolveRule($rule)
                                    : $rule
                            )
                            ->toArray();
                    })
                    ->toArray();

                $valueValidator = Valid::make(
                    $values,
                    $rules,
                    $this->messages()
                );

                if ($valueValidator->fails()) {

                    foreach ($valueValidator->errors()->messages() as $field => $messages) {

                        foreach ($messages as $message) {
                            $validator->errors()->add(
                                "technical_sheet_details.$id.settings.static.values.$field",
                                $message
                            );
                        }
                    }
                }

                foreach ($uniqueFields as $field) {

                    if (!array_key_exists($field, $row)) {
                        continue;
                    }

                    $duplicates = collect($values)
                        ->pluck($field)
                        ->filter(fn($value) => $value !== null)
                        ->duplicates();

                    if ($duplicates->contains($row[$field])) {
                        if (isset($row['id'])) {
                            $id_row = $row['id'];
                            $validator->errors()->add(
                                "technical_sheet_details.$id.settings.dinamic.values.$id_row.$field",
                                "El valor ya existe dentro de esta tabla."
                            );
                        } else {
                            $validator->errors()->add(
                                "technical_sheet_details.$id.settings.dinamic.values.$in.$field",
                                "El valor ya existe dentro de esta tabla."
                            );
                        }
                    }
                }
            }

            $empty = [];

            foreach ($this->input('production_order.production_order_details', []) as $index => $detail) {
                $model = $detail['model_type'];
                $destination = $detail['destination'] ?? null;

                if (!class_exists($model)) {
                    $validator->errors()->add(
                        "production_order_details.$model.model_type",
                        'El tipo de modelo no es válido.'
                    );
                    continue;
                }

                if ($model === Product::class) {
                    if (empty($destination)) {
                        $validator->errors()->add(
                            "production_order_details.$model.destination",
                            'El destino es obligatorio para los productos.'
                        );
                    }

                    if (array_sum(array_column($detail['sizes'], 'quantity')) === 0) {
                        $validator->errors()->add(
                            "production_order_details.$model.$destination.model_id",
                            'Es obligatorio ingresar curva.'
                        );
                        continue;
                    }
                }
            }

            if (count($empty) > 1) {
                foreach ($empty as $item) {
                    $validator->errors()->add(
                        "production_order_details.$model.$item",
                        'Es obligatorio definir la especificación de curva por talla.'
                    );
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $technical_sheet = $this->input('technical_sheet');

        foreach ($technical_sheet['technical_sheet_details'] ?? [] as $index => $detail) {
            if (isset($detail['settings']['dinamic']['insert_values'])) {
                $technical_sheet['technical_sheet_details'][$index]['settings']['dinamic']['insert_values']
                    = filter_var(
                        $detail['settings']['dinamic']['insert_values'],
                        FILTER_VALIDATE_BOOLEAN
                    );
            }
            if (isset($detail['settings']['static']['insert_values'])) {
                $technical_sheet['technical_sheet_details'][$index]['settings']['static']['insert_values']
                    = filter_var(
                        $detail['settings']['static']['insert_values'],
                        FILTER_VALIDATE_BOOLEAN
                    );
            }
        }

        $this->merge(['technical_sheet' => $technical_sheet]);
        $this->merge(['technical_sheet.physical_sample' => $this->boolean('technical_sheet.physical_sample')]);
        $this->merge(['reasigned_curve' => $this->boolean('reasigned_curve')]);
    }
}
