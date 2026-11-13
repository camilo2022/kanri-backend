<?php

namespace App\Services;

use App\Models\Piece;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductivityDetail;
use App\Models\Supply;

class ProductionOrderCurveService
{
    /**
     * Lotes de los que ya no se puede reasignar curva: tienen movimientos o están en una programación de productividad,
     * así que sus unidades ya entraron a trazabilidad o están reservadas para trabajarse.
     *
     * @return array<int, int>
     */
    public function startedOrderIds(): array
    {
        return ProductionOrderDetail::whereHas('movements')->pluck('production_order_id')
            ->merge(ProductivityDetail::query()->pluck('production_order_id'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Lo que una edición de la orden quitaría frente a lo guardado: destinos cuya curva queda en 0 (o no llega) y piezas o
     * rollos que ya no llegan con cantidad. La tela (cantidades por talla), los procesos y la reasignación no cuentan.
     *
     * @return array{zero_products: array<int, int>, delete: array<int, int>}
     */
    public function removedDetails(int $production_order_id, array $details): array
    {
        $kept = collect($details)->filter(function ($detail) {
            return match ($detail['model_type'] ?? null) {
                Product::class => collect($detail['sizes'] ?? [])->sum('quantity') > 0,
                Piece::class, Supply::class => empty($detail['rows']) && (float) data_get($detail, 'settings.quantity', 0) > 0,
                default => false,
            };
        })->map(fn ($detail) => $detail['model_type'].':'.$detail['model_id'].':'.($detail['destination'] ?? ''))->all();

        $saved = ProductionOrderDetail::withCount('production_order_detail_quantities')
            ->where('production_order_id', $production_order_id)
            ->whereIn('model_type', [Product::class, Piece::class, Supply::class])
            ->get()
            // La tela guarda cantidades por talla y no se quita aquí; los rollos guardan la cantidad en settings
            ->reject(fn ($detail) => $detail->model_type === Supply::class && $detail->production_order_detail_quantities_count > 0)
            ->reject(fn ($detail) => in_array($detail->model_type.':'.$detail->model_id.':'.($detail->destination ?? ''), $kept, true));

        return [
            'zero_products' => $saved->where('model_type', Product::class)
                ->filter(fn ($detail) => $detail->production_order_detail_quantities()->sum('quantity') > 0)
                ->pluck('id')->values()->all(),
            'delete' => $saved->whereIn('model_type', [Piece::class, Supply::class])->pluck('id')->values()->all(),
        ];
    }

    /**
     * Reasignación de curva: pasa unidades de una o varias órdenes a otra. Descuenta de cada origen por talla, en el orden
     * de sus destinos marcados, sin dejar negativos, y registra en la orden que recibe de cuál salió cada una.
     *
     * @param  array<int, array{production_order_id: int, selected_rows: array<int, string>, reassigned_details: array<int, array>}>  $origins
     */
    public function reassign(array $origins, int $production_order_id): void
    {
        foreach ($origins as $origin) {
            if (empty($origin['selected_rows'])) {
                continue;
            }

            $production_order_origin = ProductionOrder::findOrFail($origin['production_order_id']);
            $reassigned_by_size = [];

            foreach ($origin['reassigned_details'] as $detail) {
                foreach ($detail['sizes'] ?? [] as $size) {
                    $quantity = (int) ($size['quantity'] ?? 0);

                    if ($quantity > 0) {
                        $reassigned_by_size[$size['size_id']] = ($reassigned_by_size[$size['size_id']] ?? 0) + $quantity;
                    }
                }
            }

            $origin_details = ProductionOrderDetail::with('production_order_detail_quantities')
                ->where('production_order_id', $production_order_origin->id)
                ->where('model_type', Product::class)
                ->get();

            foreach ($reassigned_by_size as $size_id => $quantity_to_subtract) {
                $remaining = $quantity_to_subtract;

                foreach ($origin['selected_rows'] as $location) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $quantity = $origin_details->firstWhere('destination', $location)
                        ?->production_order_detail_quantities
                        ->firstWhere('size_id', $size_id);

                    if (! $quantity || (int) $quantity->quantity <= 0) {
                        continue;
                    }

                    $amount_to_subtract = min((int) $quantity->quantity, $remaining);

                    $quantity->quantity = (int) $quantity->quantity - $amount_to_subtract;
                    $quantity->save();

                    $remaining -= $amount_to_subtract;
                }
            }

            ProductionOrderDetail::firstOrCreate([
                'production_order_id' => $production_order_id,
                'model_type' => ProductionOrder::class,
                'model_id' => $production_order_origin->id,
                'destination' => null,
            ]);
        }
    }
}
