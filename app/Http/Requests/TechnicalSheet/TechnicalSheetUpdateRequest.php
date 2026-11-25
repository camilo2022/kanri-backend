<?php

namespace App\Http\Requests\TechnicalSheet;

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Collection;
use App\Models\Color;
use App\Models\File;
use App\Models\GarmentType;
use App\Models\Operation;
use App\Models\Product;
use App\Models\Subgroup;
use App\Models\Subprocess;
use App\Models\Supply;
use App\Models\SupplyType;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Models\Typology;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use App\Services\PermissionService;
use App\Services\ProductivityDetailService;
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
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['technical_sheets.update', 'technical_sheets.transformations.update'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:technical_sheets,id'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:now'],
            'status' => ['required', 'in:'.implode(',', [TechnicalSheet::PENDING, TechnicalSheet::REVIEW, TechnicalSheet::APPROVED, TechnicalSheet::CANCELLED])],
            'code' => ['required', 'string', 'max:11', 'unique:technical_sheets,code,'.$this->input('id').',id'],
            'product_id' => ['required', 'exists:products,id', 'unique:technical_sheets,product_id,'.$this->route('id').',id'],
            'collection_id' => ['required', 'exists:subitems,id,item_id,'.Collection::ITEM_ID],
            'garment_type_id' => ['required', 'exists:subitems,id,item_id,'.GarmentType::ITEM_ID],
            'subgroup_id' => ['required', 'exists:subitems,id,item_id,'.Subgroup::ITEM_ID],
            'wash_tone_id' => ['required', 'exists:subitems,id,item_id,'.WashTone::ITEM_ID],
            'color_id' => ['required', 'exists:subitems,id,item_id,'.Color::ITEM_ID],
            'waistband_type_id' => ['required', 'exists:subitems,id,item_id,'.WaistbandType::ITEM_ID],
            'measure_of_waistband' => ['required', 'numeric', 'min:1'],
            'boot_type_id' => ['required', 'exists:subitems,id,item_id,'.BootType::ITEM_ID],
            'yoke_type_id' => ['required', 'exists:subitems,id,item_id,'.YokeType::ITEM_ID],
            'back_type_id' => ['required', 'exists:subitems,id,item_id,'.BackType::ITEM_ID],
            'pattern_maker_id' => ['required',  'exists:employees,id,deleted_at,NULL'],
            'physical_sample' => ['required', 'boolean'],
            'number_of_buttons' => ['required', 'numeric', 'min:1'],
            'observation' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'photo_d' => ['required', 'array'],
            'photo_d.file' => ['sometimes', 'image', 'max:2048', 'dimensions:max_width='.File::MAX_IMAGE_SIDE.',max_height='.File::MAX_IMAGE_SIDE],
            'photo_t' => ['required', 'array'],
            'photo_t.file' => ['sometimes', 'image', 'max:2048', 'dimensions:max_width='.File::MAX_IMAGE_SIDE.',max_height='.File::MAX_IMAGE_SIDE],
            'technical_sheet_details' => ['required', 'array'],
            'removed_details' => ['nullable', 'array'],
            'removed_details.*.model_type' => ['required', 'in:'.Subprocess::class.','.Operation::class],
            'removed_details.*.model_id' => ['required', 'integer'],
            'technical_sheet_details.*' => ['sometimes', 'array'],
            'technical_sheet_details.*.typology' => ['nullable', 'exists:subitems,id,item_id,'.Typology::ITEM_ID.',deleted_at,NULL'],
            'technical_sheet_details.*.settings' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.dinamic' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.dinamic.values' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.dinamic.insert_values' => ['required_with:technical_sheet_details.*.settings.dinamic', 'boolean'],
            'technical_sheet_details.*.settings.static' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.static.values' => ['sometimes', 'array'],
            'technical_sheet_details.*.settings.static.insert_values' => ['required_with:technical_sheet_details.*.settings.static', 'boolean'],
            'supplies' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'No puede ser posterior a '.now()->format('Y-d-m').'.',
            'photo.max' => 'Se permite máximo :max KB.',
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
            'observation' => 'Observación',
            'description' => 'Descripción',
            'photo_d' => 'Foto delantera',
            'photo_t' => 'Foto trasera',
            'technical_sheet_details' => 'Detalles de la ficha tecnica',
            'removed_details' => 'Subprocesos y operaciones quitados de la ficha',

        ];
    }

    protected function resolveRule(string $rule): string
    {
        if (! is_string($rule)) {
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
            // Estado de cada puesto en la ficha: el que llega y, si no llega, el guardado
            $stored = $this->route('id')
                ? TechnicalSheetDetail::where('technical_sheet_id', $this->route('id'))->get()->mapWithKeys(fn ($detail) => [$detail->model_type.':'.$detail->model_id => $detail->status])
                : collect();
            $statuses = $stored->merge(collect($this->input('technical_sheet_details', []))
                ->mapWithKeys(fn ($detail) => [($detail['model_type'] ?? '').':'.($detail['model_id'] ?? '') => $detail['status'] ?? null])
                ->filter());

            // Quitar de la ficha: ni lo automático, ni lo Aprobado, ni lo que algún lote ya trabajó; quitar un subproceso quita sus operaciones
            $removed = collect();

            foreach ($this->input('removed_details', []) as $i => $item) {
                $model = in_array($item['model_type'] ?? null, [Subprocess::class, Operation::class], true) ? $item['model_type']::find($item['model_id'] ?? null) : null;

                if (! $model) {
                    $validator->errors()->add("removed_details.$i", 'El subproceso u operación no existe.');

                    continue;
                }

                if ($statuses->get($model::class.':'.$model->id) === TechnicalSheetDetail::APPROVED) {
                    $validator->errors()->add("removed_details.$i", "No se puede quitar {$model->name} de la ficha: está Aprobado.");

                    continue;
                }

                if ($error = app(ProductivityDetailService::class)->sheetRemovalError($model, (int) $this->route('id'))) {
                    $validator->errors()->add("removed_details.$i", $error);

                    continue;
                }

                $removed = $removed->push($model::class.':'.$model->id)
                    ->merge($model instanceof Subprocess ? $model->operations->map(fn ($operation) => Operation::class.':'.$operation->id) : []);
            }

            // A un proceso o subproceso Aprobado no se le agregan subprocesos ni operaciones
            foreach ($this->input('technical_sheet_details', []) as $id => $detail) {
                $type = $detail['model_type'] ?? null;

                if (! in_array($type, [Subprocess::class, Operation::class], true) || $stored->has($type.':'.($detail['model_id'] ?? ''))) {
                    continue;
                }

                $model = $type::with($type === Subprocess::class ? 'process' : 'subprocess')->find($detail['model_id'] ?? null);
                $parent = collect($type === Subprocess::class ? $model?->process : $model?->subprocess)
                    ->first(fn ($parent) => $statuses->get($parent::class.':'.$parent->id) === TechnicalSheetDetail::APPROVED);

                if ($parent) {
                    $validator->errors()->add("technical_sheet_details.$id", "No se puede agregar {$model->name}: {$parent->name} está Aprobado.");
                }
            }

            // Lo que va en secuencia necesita en la ficha alguno de sus anteriores (contando lo que queda tras quitar)
            $keys = collect($this->input('technical_sheet_details', []))
                ->map(fn ($detail) => ($detail['model_type'] ?? '').':'.($detail['model_id'] ?? ''))
                ->merge($this->route('id') ? TechnicalSheetDetail::where('technical_sheet_id', $this->route('id'))->get()->map(fn ($detail) => $detail->model_type.':'.$detail->model_id) : [])
                ->unique()
                ->diff($removed)
                ->values();

            foreach (app(ProductivityDetailService::class)->sheetSequenceErrors($keys, $this->integer('wash_tone_id') ?: null) as $id => $message) {
                $validator->errors()->add("technical_sheet_details.$id", $message);
            }

            $product = Product::find($this->integer('product_id'));
            $supply_types = SupplyType::with('supplies')
                ->where('settings->in_technical_sheet', true)->get();

            $messages = [];

            $aux = [
                'D' => 'photo_d.file',
                'T' => 'photo_t.file',
            ];

            foreach ($aux as $suffix => $field) {
                $photo = $this->file($field);

                if ($this->hasFile($field)) {
                    $expectedName = $product->code.'-'.$suffix;

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

            foreach ($supply_types as $supply_type) {
                $has_supplies = $supply_type->supplies
                    ->pluck('id')
                    ->intersect($this->input('supplies', []))
                    ->isNotEmpty();

                if (! $has_supplies) {
                    $validator->errors()->add("supply.$supply_type->id", 'Debe seleccionar una supplye');
                }
            }

            $supply_type_ids = [];

            foreach ($this->input('supplies', []) as $key => $supply_id) {
                $supply = Supply::with('supply_type')->find($supply_id);

                if (in_array($supply?->supply_type->first()?->id, $supply_type_ids)) {
                    $validator->errors()->add("supplies.$key", 'La supplye pertenece a un tipo de insumo ya registrado.');
                }
            }

            foreach ($this->input('technical_sheet_details', []) as $detail) {

                $settings = $detail['settings'] ?? [];
                $id = $detail['model_id'] ?? null;

                if (! isset($settings['dinamic']) && ! isset($settings['static'])) {
                    continue;
                }

                if (isset($settings['dinamic'])) {
                    $body = $settings['dinamic']['body'] ?? [];
                    $values = $settings['dinamic']['values'] ?? [];
                    $insertValues = $settings['dinamic']['insert_values'] ?? false;

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

                            if (! $field) {
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

                                if (! array_key_exists($field, $row)) {
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
                                            'El valor ya existe dentro de esta tabla.'
                                        );
                                    } else {
                                        $validator->errors()->add(
                                            "technical_sheet_details.$id.settings.dinamic.values.$in.$field",
                                            'El valor ya existe dentro de esta tabla.'
                                        );
                                    }

                                    continue;
                                }

                                $seen[$value] = true;
                            }
                        }
                    }
                }

                if (isset($settings['static'])) {
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

                            if (! $field) {
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
                                    fn ($rule) => is_string($rule)
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
                        if (! array_key_exists($field, $row)) {
                            continue;
                        }

                        $duplicates = collect($values)
                            ->pluck($field)
                            ->filter(fn ($value) => $value !== null)
                            ->duplicates();

                        if ($duplicates->contains($row[$field])) {
                            if (isset($row['id'])) {
                                $id_row = $row['id'];
                                $validator->errors()->add(
                                    "technical_sheet_details.$id.settings.dinamic.values.$id_row.$field",
                                    'El valor ya existe dentro de esta tabla.'
                                );
                            } else {
                                $validator->errors()->add(
                                    "technical_sheet_details.$id.settings.dinamic.values.$in.$field",
                                    'El valor ya existe dentro de esta tabla.'
                                );
                            }
                        }
                    }
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $details = $this->input('technical_sheet_details', []);

        foreach ($details as $id => &$detail) {
            if (isset($detail['settings']['dinamic']['insert_values'])) {
                $detail['settings']['dinamic']['insert_values'] =
                    filter_var($detail['settings']['dinamic']['insert_values'], FILTER_VALIDATE_BOOLEAN);
            }

            if (isset($detail['settings']['static']['insert_values'])) {
                $detail['settings']['static']['insert_values'] =
                    filter_var($detail['settings']['static']['insert_values'], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $this->merge(['technical_sheet_details' => $details]);
        $this->merge(['physical_sample' => $this->boolean('physical_sample')]);
        $this->merge(['id' => $this->route('id')]);
    }
}
