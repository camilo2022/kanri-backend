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
use Illuminate\Support\Facades\Validator as Valid;

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
            'technical_sheet_details' => ['required', 'array'],
            'technical_sheet_details.*' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.dinamic' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.dinamic.values' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.dinamic.insert_values' => ['required_with:technical_sheet_details.*.settings.dinamic', 'boolean'],
            'technical_sheet_details.*.settings.static' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.static.values' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.static.insert_values' => ['required_with:technical_sheet_details.*.settings.static', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'required_with' => 'Es requerido.',
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

    protected function validationMessages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'numeric' => 'Debe ser un número.',
            'min' => 'Debe tener al menos :min.',
            'max' => 'No puede superar :max.',
            'exists' => 'El valor seleccionado no existe.',
            'string' => 'Debe ser texto.',
            'in' => 'Seleccione una opción válida.',
            'nullable' => '',

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

            foreach ($this->input('technical_sheet_details', []) as $detail) {

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
                                ->map(
                                    fn($rule) =>
                                    is_string($rule)
                                        ? $this->resolveRule($rule)
                                        : $rule
                                )
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
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['physical_sample' => $this->boolean('physical_sample')]);
        $this->merge(['id' => $this->route('id')]);
    }
}
