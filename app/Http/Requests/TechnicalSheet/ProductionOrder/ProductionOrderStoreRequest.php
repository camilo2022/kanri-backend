<?php

namespace App\Http\Requests\TechnicalSheet\ProductionOrder;

use App\Models\Color;
use App\Models\Piece;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Size;
use App\Models\TechnicalSheet;
use App\Models\Supply;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductionOrderStoreRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:now'],
            'technical_sheet_id' => ['required', 'exists:technical_sheets,id'],
            'efficiency' => ['required', 'numeric', 'min:1'],
            'cut' => ['required', 'string', 'uppercase', 'max:2'],
            'fabric_id' => ['required', 'exists:subitems,id,item_id,' . Supply::ITEM_ID . ',deleted_at,NULL'],
            'color_id' => ['required', 'exists:subitems,id,item_id,' . Color::ITEM_ID . ',deleted_at,NULL'],
            'width' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999.99'],
            'pocket_fabric' => ['required', 'boolean'],
            'observation' =>  ['nullable', 'string', 'max:1000'],
            'status' =>  ['required', 'in:' . implode(',', [ProductionOrder::PENDING, ProductionOrder::APPROVED, ProductionOrder::CANCELLED])],
            'pieces' => ['nullable', 'array'],
            'pieces.*.quantity' => ['required', 'integer', 'min:1'],
            'rolls' => ['nullable', 'array'],
            'rolls.*.quantity' => ['required', 'numeric', 'min:1'],
            'production_order_details' => ['required', 'array', 'min:1', 'max:5'],
            'production_order_details.*.model_type' => ['required',  'in:' . implode(',', [Product::class, Supply::class, Color::class])],
            'production_order_details.*.model_id' => ['nullable', 'numeric'],
            'production_order_details.*.destination' => ['nullable', 'string', 'in:NACIONAL,MEDELLIN,STARA'],
            'settings' => ['required', 'array'],
            'settings.*.large' => ['required', 'numeric', 'min:1'],
            'settings.*.quantity' => ['required', 'integer', 'min:1', 'distinct'],
            'strokes_file' => ['nullable', 'array'],
            'strokes_file.file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'strokes_file.strokes_file_id' => ['nullable', 'exists:files,id,model_type,' . ProductionOrder::class],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'date_format' => 'Formato inválido. Permitido :format.',
            'date.before_or_equal' => 'No puede ser posterior a :date.',
            'exists' => 'No registrado en el sistema.',
            'boolean' => 'Debe ser un dato tipo si/no',
            'numeric' => 'Debe ser un dato númerico',
            'min' => 'El valor debe ser mayor o igual a :min',
            'production_order_details.max' => 'El valor debe ser menor o igual a :min',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'integer' => 'Debe ser un dato númerico',
            'distinct' => 'Debe ser un valor unico'
        ];
    }

    public function attributes(): array
    {


        return [
            'date' => 'Fecha de aprobación de la orden de producción',
            'technical_sheet_id' => 'Identificador de la ficha técnica',
            'efficiency' => 'Eficiencia',
            'cut' => 'Identificador de lote',
            'fabric_id' => 'Identificador de la tela',
            'width' => 'Ancho de la tela',
            'pocket_fabric' => 'Indicador de uso de tela de bolsillo',
            'observation' =>  'Observación',
            'status' => 'Estado de la orden de producción',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasFile = $this->hasFile('strokes_file.file');
            $hasFileId = !empty($this->input('strokes_file.strokes_file_id'));

            if (!$hasFile && !$hasFileId) {
                $validator->errors()->add(
                    'strokes_file',
                    'Debe seleccionar un archivo o indicar un archivo existente.'
                );
            }

            if (!empty($this->fabric_id)) {
                $supply = Supply::with('supply_type')->find($this->fabric_id);
                $settings = $supply->supply_type->first()->settings;

                //Valido que el color este asociado con la tela
                if (!empty($this->color_id)) {
                    $exists = $supply
                        ->related(Color::class)
                        ->where('id', $this->color_id)
                        ->exists();

                    if (!$exists) {
                        $validator->errors()->add(
                            'color_id',
                            'El color seleccionado no esta asociado a la tela.'
                        );
                    }
                }

                //Valido que el tipo de insumo tela este asociado con la orden de producción
                if (empty($settings->in_production_order) && $settings->paragraph !== 'fabric') {
                    $validator->errors()->add(
                        'fabric_id',
                        "El tipo de insumo no es apto para asociar una orden de producción"
                    );
                }
            }

            //Valido que las piezas existan
            foreach (array_keys($this->pieces ?? []) as $piece_id) {
                if (!Piece::where('id', $piece_id)->exists()) {
                    $validator->errors()->add(
                        "pieces.$piece_id",
                        "Pieza no registrada."
                    );
                }
            }

            //Valido que los rollos existen
            foreach (array_keys($this->rolls ?? []) as $roll_id) {
                if (!Supply::where('id', $roll_id)->exists()) {
                    $validator->errors()->add(
                        "rolls.$roll_id",
                        "Rollo no registrado."
                    );
                }
            }

            $photo = $this->file('strokes_file.file');

            if ($this->hasFile('strokes_file.file')) {
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

            $empty = [];

            foreach ($this->production_order_details as $index => $detail) {
                $model = $detail['model_type'];
                $destination = $detail['destination'] ?? null;

                if (!class_exists($model)) {
                    $validator->errors()->add(
                        "production_order_details.$model.model_type",
                        'El tipo de modelo no es válido.'
                    );
                    continue;
                }

                if (!isset($detail['model_id'])) {
                    $validator->errors()->add(
                        "production_order_details.$model.$destination.model_id",
                        'No hay identificador del detalle.'
                    );
                    continue;
                }

                if (!$model::whereKey($detail['model_id'])->exists()) {
                    $validator->errors()->add(
                        "production_order_details.$model.model_id",
                        'El modelo seleccionado no existe.'
                    );
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

                    if ($destination !== 'STARA') {
                        if (empty($detail['sizes'])) {
                            $empty[] = $destination;
                        }
                    }
                }

                if ($model === Supply::class) {
                    if ($detail['model_id'] !== $this->fabric_id) {
                        $validator->errors()->add(
                            "production_order_details.$model.model_id",
                            'El modelo seleccionado no corresponde con la tela seleccionada.'
                        );
                        continue;
                    }

                    if (empty($detail['rows'])) {
                        $validator->errors()->add(
                            "production_order_details.$model.$destination.rows",
                            'El detalle no cuenta con distribución por filas.'
                        );
                    } else {
                        $rows = [];

                        foreach ($detail['rows'] as $i => $row) {
                            if (empty($row['sizes']) || !is_array($row['sizes'])) {
                                $rows[] = $i;
                                continue;
                            }
                            foreach ($row['sizes'] as $a => $size) {
                                if (empty($size['size_id']) || !Size::whereKey($size['size_id'])->exists()) {
                                    $validator->errors()->add(
                                        "production_order_details.$index.rows.$i.sizes.$a",
                                        'La talla ingresada no existe.'
                                    );
                                }
                            }
                        }

                        if (count($rows) === count($detail['rows'])) {
                            foreach ($rows as $item) {
                                $validator->errors()->add(
                                    "production_order_details.fabric.rows.$item.sizes",
                                    'El detalle no cuenta con distribución por tallas.'
                                );
                            }
                        }
                    }
                }

                if ($model === Color::class) {
                    if ($detail['model_id'] !== $this->color_id) {
                        $validator->errors()->add(
                            "production_order_details.$index.model_id",
                            'El modelo seleccionado no corresponde con el color de la tela seleccionada.'
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

            /*
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
                    'paragraph',
                    "Ya existe un tipo de insumo configurado con este apartado. Solo puede existir uno por apartado."
                );
            }*/
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['pocket_fabric' => $this->boolean('pocket_fabric')]);
    }
}
