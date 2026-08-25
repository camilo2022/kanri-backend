<?php

namespace App\Http\Requests\Management\Production;

use App\Models\Color;
use App\Models\Piece;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\Size;
use App\Models\TechnicalSheet;
use App\Models\Variant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class ProductionOrderSaveRequest extends FormRequest
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
            'id' => ['nullable', 'exists:production_orders,id'],
            'color_id' => ['required', 'exists:subitems,id,item_id,' . Color::ITEM_ID . ',deleted_at,NULL'],
            'date' => ['required', 'date_format:Y-m-d'],
            'technical_sheet_id' => ['required', 'exists:technical_sheets,id'],
            'cut' => ['required', 'string', 'uppercase', 'max:2'],
            'status' =>  ['required', 'in:' . implode(',', [ProductionOrder::PENDING, ProductionOrder::APPROVED, ProductionOrder::CANCELLED])],
            'production_order_details' => ['required', 'array', 'min:1', 'max:5'],
            'production_order_details.*.model_type' => ['required',  'in:' . implode(',', [Product::class, Variant::class, Color::class])],
            'production_order_details.*.model_id' => ['nullable', 'numeric'],
            'production_order_details.*.destination' => ['nullable', 'string', 'in:NACIONAL,MEDELLIN,STARA'],
            'reasigned_curve' => ['boolean'],
            'production_order_id' => ['required_if:reasigned_curve,true', 'exists:production_orders,id'],
            'selected_rows' => ['required_if:reasigned_curve,true', 'array', 'min:1', 'max:3'],
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

    private function getNextCut(string $cut): string
    {
        $cut = strtoupper(trim($cut));
        return chr(ord($cut) + 1);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->id === null) {
                $current_cut = strtoupper(trim($this->cut ?? ''));

                if ($this->technical_sheet_id && $current_cut) {
                    $last_production_order = ProductionOrder::where(
                        'technical_sheet_id',
                        $this->technical_sheet_id
                    )
                        ->when($this->id, function ($query) {
                            $query->where('id', '!=', $this->id);
                        })
                        ->orderByDesc('id')
                        ->first();

                    if ($last_production_order) {
                        $lastCut = strtoupper(trim($last_production_order->cut));
                        $expectedCut = $this->getNextCut($lastCut);
                        if ($current_cut !== $expectedCut) {
                            $validator->errors()->add(
                                'cut',
                                "No es consecutivo."
                            );
                        }
                    } else {
                        if ($current_cut !== 'A') {
                            $validator->errors()->add(
                                'cut',
                                'El primer corte de esta ficha técnica debe ser A.'
                            );
                        }
                    }
                }
            }

            $details = $this->production_order_details ?? [];

            $hasVariant = collect($details)->contains(function ($detail) {
                return ($detail['model_type'] ?? null) === Variant::class;
            });

            if (!$hasVariant) {
                $validator->errors()->add("fabric_id", 'Es obligatorio.');
            }

            $hasProducts = collect($details)->contains(function ($detail) {
                return ($detail['model_type'] ?? null) === Product::class;
            });

            if (!$hasProducts) {
                $validator->errors()->add("curve", 'Es obligatorio.');
            }

            $empty = [];

            foreach ($details as $index => $detail) {
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

                if ($model === Variant::class) {
                    if (!empty($detail['model_id'])) {
                        $variant = Variant::with('supply_type')->find($detail['model_id']);
                        $settings = $variant->supply_type->first()->settings;

                        if (!empty($this->color_id)) {
                            $exists = $variant
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

                        if (empty($settings->in_production_order) && $settings->paragraph !== 'fabric') {
                            $validator->errors()->add(
                                'fabric_id',
                                "El tipo de insumo no es apto para asociar una orden de producción"
                            );
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

            if ($this->reasigned_curve) {
                $production_order_origin = ProductionOrder::findOrFail($this->input('production_order_id'));

                if (!$production_order_origin) {
                    $validator->errors()->add(
                        'production_order_id',
                        'Es requerida una orden de origen válida para realizar la reasignación.'
                    );

                    return;
                }

                $selected_destinations = array_values(
                    array_intersect(
                        ['NACIONAL', 'MEDELLIN', 'STARA'],
                        $this->input('selected_rows', [])
                    )
                );

                if (empty($selected_destinations)) {
                    $validator->errors()->add(
                        'selected_rows',
                        'Debe seleccionar al menos un destino de origen para realizar la reasignación.'
                    );
                    return;
                }

                $origin_details = ProductionOrderDetail::with('production_order_detail_quantities')
                    ->where('production_order_id', $production_order_origin->id)
                    ->where('model_type', Product::class)
                    ->get();

                $available_curve = [];

                foreach ($origin_details as $detail) {
                    $destination = $detail->destination;

                    if (!in_array($destination, $selected_destinations)) {
                        continue;
                    }

                    foreach ($detail->production_order_detail_quantities as $quantity) {
                        $available_curve[$destination][$quantity->size_id] =
                            (int) $quantity->quantity;
                    }
                }

                foreach ($details as $detail) {

                    if (($detail['model_type'] ?? null) !== Product::class) {
                        continue;
                    }

                    foreach ($detail['sizes'] ?? [] as $size) {

                        $size_id = $size['size_id'];
                        $remaining = (int) ($size['quantity'] ?? 0);

                        if ($remaining <= 0) {
                            continue;
                        }

                        foreach ($selected_destinations as $destination) {
                            $available = $available_curve[$destination][$size_id] ?? 0;

                            if ($available <= 0) {
                                continue;
                            }

                            $taken = min($available, $remaining);
                            $remaining -= $taken;

                            if ($remaining <= 0) {
                                break;
                            }
                        }

                        if ($remaining > 0) {
                            $validator->errors()->add(
                                "production_order_details.$size_id.quantity",
                                "La cantidad solicitada para la talla {$size_id} supera la cantidad disponible en los destinos seleccionados de la orden de origen."
                            );
                        }
                    }
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['pocket_fabric' => $this->boolean('pocket_fabric')]);
        $this->merge(['reasigned_curve' => $this->boolean('reasigned_curve')]);
    }
}
