<?php

namespace App\Http\Controllers;

use App\Http\Requests\Management\Collection\CollectionFindRequest;
use App\Http\Requests\Management\Production\ProductionOrderSaveRequest;
use App\Http\Resources\Management\Production\CollectionResource;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderResource;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\TechnicalSheet;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManagementProductionController extends Controller
{
    use ApiMessage, ApiResponser;

    public function find(CollectionFindRequest $request, $id)
    {
        try {
            $collection = Collection::with([
                'item',
                'technical_sheets' => function ($query) {
                    $query->where('status', '!=', TechnicalSheet::PENDING)
                        ->with(['technical_sheet_details', 'product' => ['trademark' => ['sizes'], 'subcategory' => ['category']], 'garment_type', 'wash_tone', 'boot_type', 'photo_d', 'photo_t', 'products', 'production_orders' => ['production_order_details' => ['production_order_detail_quantities', 'model'], 'fabric' => ['model'], 'color', 'supplier', 'reassignments']]);
                },
            ])->findOrFail($id);

            return $this->successResponse(
                new CollectionResource($collection),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function save(ProductionOrderSaveRequest $request)
    {
        $production_order = ProductionOrder::firstOrNew(['id' => $request->input('id')]);

        if (! $production_order->exists) {
            $result = DB::select('CALL generate_production_order_code()');
            $production_order->consecutive = $result[0]->consecutive;
            $production_order->uuid = Str::uuid();
        }

        $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
        $production_order->cut = $request->input('cut');
        $production_order->status = $request->input('status');
        $production_order->production_place = $request->input('production_place');
        $production_order->priority = $request->input('priority');
        $production_order->settings = $request->input('settings');

        if ($request->input('supplier_id')) {
            $production_order->supplier_id = $request->integer('supplier_id');
        }

        $production_order->save();

        $production_order->color()->sync([$request->input('color_id')]);

        logger($request->boolean('reasigned_curve'));
        if ($request->boolean('reasigned_curve')) {
            logger([$request->input('reassigned_origins', []), $production_order->id]);
            $this->update_origin_curve($request->input('reassigned_origins', []), $production_order->id);
        }

        $this->save_details($request->input('production_order_details', []), $production_order->id);

        $production_order->load(['production_order_details' => ['model', 'production_order_detail_quantities']]);

        return $this->successResponse(
            new ProductionOrderResource($production_order),
            $this->getMessage('Success'),
            200
        );
    }

    public function save_details(array $production_order_details, int $production_order_id)
    {
        try {
            foreach ($production_order_details as $production_order_detail_item) {
                $quantities = [];

                if ($production_order_detail_item['model_type'] === Product::class) {
                    $quantities = $production_order_detail_item['sizes'];

                    if (array_sum(array_column($quantities, 'quantity')) === 0) {
                        continue;
                    }
                }

                if ($production_order_detail_item['id']) {
                    $production_order_detail = ProductionOrderDetail::findOrFail($production_order_detail_item['id']);

                    $production_order_detail->update([
                        'model_id' => $production_order_detail_item['model_id'],
                        'model_type' => $production_order_detail_item['model_type'],
                        'destination' => $production_order_detail_item['destination'],
                        'settings' => $production_order_detail_item['settings'] ?? [],
                    ]);
                } else {
                    $production_order_detail = ProductionOrderDetail::create([
                        'production_order_id' => $production_order_id,
                        'model_id' => $production_order_detail_item['model_id'],
                        'model_type' => $production_order_detail_item['model_type'],
                        'destination' => $production_order_detail_item['destination'],
                        'settings' => $production_order_detail_item['settings'] ?? [],
                    ]);
                }

                if ($production_order_detail['model_type'] === Product::class) {
                    foreach ($production_order_detail_item['sizes'] as $size) {
                        ProductionOrderDetailQuantities::updateOrCreate(
                            [
                                'production_order_detail_id' => $production_order_detail->id,
                                'size_id' => $size['size_id'],
                            ],
                            [
                                'quantity' => $size['quantity'],
                            ]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function update_origin_curve(array $reassigned_origins, int $production_order_id)
    {
        try {
            foreach ($reassigned_origins as $reassigned_origin) {
                logger($reassigned_origin);
                $production_order_origin = ProductionOrder::findOrFail($reassigned_origin['production_order_id']);

                if (empty($reassigned_origin['selected_rows'])) {
                    return;
                }

                $reassigned_by_size = [];

                foreach ($reassigned_origin['reassigned_details'] as $detail) {

                    foreach ($detail['sizes'] ?? [] as $size) {

                        $size_id = $size['size_id'];
                        $quantity = (int) ($size['quantity'] ?? 0);

                        if ($quantity <= 0) {
                            continue;
                        }

                        $reassigned_by_size[$size_id] =
                            ($reassigned_by_size[$size_id] ?? 0) + $quantity;
                    }
                }

                $origin_details = ProductionOrderDetail::with('production_order_detail_quantities')
                    ->where('production_order_id', $production_order_origin->id)
                    ->where('model_type', Product::class)
                    ->get();

                foreach ($reassigned_by_size as $size_id => $quantity_to_subtract) {

                    $remaining = $quantity_to_subtract;

                    foreach ($reassigned_origin['selected_rows'] as $location) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $detail = $origin_details->first(function ($item) use ($location) {
                            return $item->destination === $location;
                        });

                        if (! $detail) {
                            continue;
                        }

                        $quantity = $detail->production_order_detail_quantities
                            ->firstWhere('size_id', $size_id);

                        if (! $quantity) {
                            continue;
                        }

                        $available = (int) $quantity->quantity;

                        if ($available <= 0) {
                            continue;
                        }

                        $amount_to_subtract = min($available, $remaining);

                        $quantity->quantity = $available - $amount_to_subtract;
                        $quantity->save();

                        $remaining -= $amount_to_subtract;
                    }
                }

                ProductionOrderDetail::firstOrCreate(
                    [
                        'production_order_id' => $production_order_id,
                        'model_type' => ProductionOrder::class,
                        'model_id' => $production_order_origin->id,
                        'destination' => null,
                    ]
                );
            }

        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
