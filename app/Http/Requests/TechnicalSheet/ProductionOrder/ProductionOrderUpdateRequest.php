<?php

namespace App\Http\Requests\TechnicalSheet\ProductionOrder;

use App\Models\Color;
use App\Models\Piece;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\Supply;
use App\Services\PermissionService;
use App\Services\ProductionOrderCurveService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductionOrderUpdateRequest extends FormRequest
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
        return ['technical_sheets.production_orders.update'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:production_orders,id'],
            'technical_sheet_id' => ['required', 'exists:technical_sheets,id'],
            'efficiency' => ['required', 'numeric', 'min:1'],
            'cut' => ['required', 'string', 'uppercase', 'max:2'],
            'fabric_id' => ['required', 'exists:subitems,id,item_id,'.Supply::ITEM_ID.',deleted_at,NULL'],
            'color_id' => ['required', 'exists:subitems,id,item_id,'.Color::ITEM_ID],
            'width' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999.99'],
            'pocket_fabric' => ['required', 'boolean'],
            'observation' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:'.implode(',', [ProductionOrder::PENDING, ProductionOrder::APPROVED, ProductionOrder::CANCELLED])], 'production_place' => ['required', 'string', 'in:BLESS,SATELITE'],
            'supplier_id' => ['nullable', 'required_if:production_place,SATELITE', 'exists:subitems,id,item_id,'.Supplier::ITEM_ID],
            'reasigned_curve' => ['boolean'],
            'reassigned_origins' => ['nullable', 'required_if:reasigned_curve,true', 'array', 'min:1'],
            'reassigned_origins.*.production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'reassigned_origins.*.selected_rows' => ['required', 'array', 'min:1'],
            'reassigned_origins.*.selected_rows.*' => ['string', 'in:NACIONAL,MEDELLIN,STARA'],
            'reassigned_origins.*.reassigned_details' => ['required', 'array', 'min:1'],
            'production_order_details' => ['required', 'array'],
            'production_order_details.*.model_type' => ['required',  'in:'.implode(',', [Product::class, Supply::class, Color::class, Piece::class])],
            'production_order_details.*.model_id' => ['nullable', 'numeric'],
            'production_order_details.*.destination' => ['nullable', 'string', 'in:NACIONAL,MEDELLIN,STARA'],
            'settings' => ['required', 'array'],
            'settings.*.large' => ['required', 'numeric'],
            'settings.*.quantity' => ['required', 'integer', 'min:1', 'distinct'],
            'strokes_file' => ['nullable', 'array'],
            'strokes_file.file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'production_order_details.max' => 'El valor debe ser menor o igual a :min',
        ];
    }

    public function attributes(): array
    {

        return [
            'id' => 'Identificador de la orden de producción',
            'technical_sheet_id' => 'Identificador de la ficha técnica',
            'efficiency' => 'Eficiencia',
            'cut' => 'Identificador de lote',
            'fabric_id' => 'Identificador de la tela',
            'color_id' => 'Identificador del color',
            'width' => 'Ancho de la tela',
            'pocket_fabric' => 'Indicador de uso de tela de bolsillo',
            'reasigned_curve' => 'Indicador de reasignación de curva',
            'production_order_id' => 'Identificador de la orden de producción de reasiganción',
            'observation' => 'Observación',
            'status' => 'Estado de la orden de producción',
            'pieces' => 'Piezas',
            'rolls' => 'Rollos',
            'production_order_details' => 'Detalles de la orden de producción',
            'settings' => 'Configuracion de la orden de producción',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->boolean('reasigned_curve')) {
                $started_orders = app(ProductionOrderCurveService::class)->startedOrderIds();

                foreach ($this->input('reassigned_origins') ?? [] as $index => $origin) {
                    if (in_array((int) ($origin['production_order_id'] ?? 0), $started_orders, true)) {
                        $validator->errors()->add(
                            "reassigned_origins.$index.production_order_id",
                            'No se puede reasignar curva de un lote que ya se empezó a trabajar o está programado.'
                        );
                    }
                }
            }

            // Si los datos básicos ya fallaron no hay nada coherente que validar a fondo (evita errores 500 con datos vacíos)
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Las filas de tela que llegan con id deben ser de esta orden (si no, se moverían de otra)
            $row_ids = collect($this->input('production_order_details', []))
                ->flatMap(fn ($detail) => collect($detail['rows'] ?? [])->flatMap(fn ($row) => collect($row['sizes'] ?? [])->pluck('id')))
                ->filter()
                ->unique();
            $own_ids = ProductionOrderDetailQuantities::whereHas('production_order_detail', fn ($q) => $q->where('production_order_id', $this->route('id')))->pluck('id');
            if ($row_ids->diff($own_ids)->isNotEmpty()) {
                $validator->errors()->add('fabric_id', 'Hay cantidades de tela que no pertenecen a esta orden.');
            }

            // En un lote que ya se empezó a trabajar no se quitan destinos, piezas ni rollos
            $removed = app(ProductionOrderCurveService::class)->removedDetails((int) $this->route('id'), $this->input('production_order_details', []));
            if (in_array((int) $this->route('id'), app(ProductionOrderCurveService::class)->startedOrderIds(), true)) {
                foreach (ProductionOrderDetail::whereIn('id', $removed['zero_products'])->pluck('destination') as $destination) {
                    $validator->errors()->add('production_order_details.'.Product::class.".$destination.model_id", 'No se puede quitar este destino: el lote ya se empezó a trabajar.');
                }
                $deleted_types = ProductionOrderDetail::whereIn('id', $removed['delete'])->pluck('model_type');
                if ($deleted_types->contains(Piece::class)) {
                    $validator->errors()->add('pieces', 'No se pueden quitar piezas: el lote ya se empezó a trabajar.');
                }
                if ($deleted_types->contains(Supply::class)) {
                    $validator->errors()->add('rolls', 'No se pueden quitar rollos: el lote ya se empezó a trabajar.');
                }
            }
            $exists = ProductionOrder::where('technical_sheet_id', $this->input('technical_sheet_id'))
                ->where('cut', $this->input('cut'))
                ->when($this->route('id'), function ($query) {
                    $query->where('id', '<>', $this->route('id'));
                })
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'cut',
                    'Ya existe un lote con ese corte para esta referencia.'
                );
            }

            if (! empty($this->fabric_id)) {
                $supply = Supply::with('supply_type')->find($this->fabric_id);
                $settings = $supply->supply_type->first()->settings;

                // Valido que el color este asociado con la tela; la orden que ya tenía esa tela y ese color los conserva aunque
                // después se haya quitado la relación o desactivado el color
                if (! empty($this->color_id)) {
                    $exists = $supply
                        ->related(Color::class)
                        ->where('id', $this->color_id)
                        ->exists();

                    $order = ProductionOrder::with(['fabric'])->find($this->route('id'));
                    $unchanged = $order
                        && (int) $order->fabric?->model_id === (int) $this->fabric_id
                        && $order->color()->withTrashed()->where('subitems.id', $this->color_id)->exists();

                    if (! $exists && ! $unchanged) {
                        $validator->errors()->add(
                            'color_id',
                            'El color seleccionado no esta asociado a la tela.'
                        );
                    }
                }

                // Valido que el tipo de insumo tela este asociado con la orden de producción
                if (empty($settings->in_production_order) && $settings->paragraph !== 'fabric') {
                    $validator->errors()->add(
                        'fabric_id',
                        'El tipo de insumo no es apto para asociar una orden de producción'
                    );
                }
            }

            // Valido que las piezas existan
            foreach (array_keys($this->pieces ?? []) as $piece_id) {
                if (! Piece::where('id', $piece_id)->exists()) {
                    $validator->errors()->add(
                        "pieces.$piece_id",
                        'Pieza no registrada.'
                    );
                }
            }

            // Valido que los rollos existen
            foreach (array_keys($this->rolls ?? []) as $roll_id) {
                if (! Supply::where('id', $roll_id)->exists()) {
                    $validator->errors()->add(
                        "rolls.$roll_id",
                        'Rollo no registrado.'
                    );
                }
            }

            $empty = [];

            foreach ($this->production_order_details as $index => $detail) {
                $model = $detail['model_type'];
                $destination = $detail['destination'] ?? null;
                $quantity = data_get($detail, 'settings.quantity', null);

                if (! class_exists($model)) {
                    $validator->errors()->add(
                        "production_order_details.$model.model_type",
                        'El tipo de modelo no es válido.'
                    );

                    continue;
                }

                if (! isset($detail['model_id'])) {
                    $validator->errors()->add(
                        "production_order_details.$model.$destination.model_id",
                        'No hay identificador del detalle.'
                    );

                    continue;
                }

                if (! $model::whereKey($detail['model_id'])->exists()) {
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
                    if (! empty($detail['rows'])) {
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
                                if (empty($row['sizes']) || ! is_array($row['sizes'])) {
                                    $rows[] = $i;

                                    continue;
                                }
                                foreach ($row['sizes'] as $a => $size) {
                                    if (empty($size['size_id']) || ! Size::whereKey($size['size_id'])->exists()) {
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
                    } else {
                        $supply = Supply::find($detail['model_id']);

                        if (! $supply) {
                            continue;
                        }

                        if ($quantity === null || $quantity === '') {
                            $validator->errors()->add(
                                "production_order_details.$index.settings.quantity",
                                'La cantidad del rollo es obligatoria.'
                            );

                            continue;
                        }

                        if (! is_numeric($quantity) || $quantity <= 0) {
                            $validator->errors()->add(
                                "production_order_details.$index.settings.quantity",
                                'La cantidad del rollo debe ser un valor numérico mayor que cero.'
                            );

                            continue;
                        }

                        $quantity = (float) $quantity;

                        $supply_quantity = (float) data_get($supply, 'settings.values.meters', 0);

                        $used_quantity_query = ProductionOrderDetail::query()
                            ->where('model_type', Supply::class)
                            ->where('model_id', $detail['model_id']);

                        if ($this->route('id')) {
                            $production_order_id = $this->route('id');

                            $used_quantity_query->where(function ($query) use ($production_order_id) {
                                $query->where('production_order_id', '!=', $production_order_id);
                            });
                        }

                        $used_quantity = $used_quantity_query
                            ->get()
                            ->sum(function ($production_detail) {
                                return (float) data_get(
                                    $production_detail,
                                    'settings.quantity',
                                    0
                                );
                            });

                        $available_quantity = $supply_quantity - $used_quantity;

                       if ($quantity > $available_quantity) {
                            $validator->errors()->add(
                                "production_order_details.$index.settings.quantity",
                                "La cantidad solicitada ({$quantity}) supera la cantidad disponible del rollo ({$available_quantity})."
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
        $this->merge(['reasigned_curve' => $this->boolean('reasigned_curve')]);
        $this->merge(['id' => $this->route('id')]);
    }
}
