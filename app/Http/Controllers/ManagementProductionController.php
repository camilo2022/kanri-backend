<?php

namespace App\Http\Controllers;

use App\Http\Requests\Management\Collection\CollectionFindRequest;
use App\Http\Requests\Management\Production\ProductionOrderSaveRequest;
use App\Http\Requests\Management\TechnicalSheet\TechnicalSheetSaveRequest;
use App\Http\Resources\Management\Collection\CollectionResource;
use App\Http\Resources\Management\Collection\TechnicalSheetResource;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderResource;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManagementProductionController extends Controller
{
    use ApiMessage, ApiResponser;

    public function save(ProductionOrderSaveRequest $request)
    {
        $production_order = ProductionOrder::firstOrNew(['id' => $request->input('id')]);

        if (!$production_order->exists) {
            $result = DB::select("CALL generate_production_order_code()");
            $production_order->consecutive = $result[0]->consecutive;
            $production_order->uuid = Str::uuid();
        }

        if ($request->boolean('reasigned_curve')) {
            $production_order->model_id = $request->input('production_order_id');
            $production_order->model_type = ProductionOrder::class;
        }

        $production_order->date = $request->input('date');
        $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
        $production_order->cut = $request->input('cut');
        $production_order->status = $request->input('status');

        $production_order->save();

        $production_order->color()->sync([$request->input('color_id')]);

        $this->save_details($request->input('production_order_details', []), $production_order->id);

        if ($request->boolean('reasigned_curve')) {
            $this->update_origin_curve($request->integer('production_order_id'), $request->input('production_order_details', []), $request->input('selected_rows', []));
        }

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
                    ]);
                } else {
                    $production_order_detail = ProductionOrderDetail::create([
                        'production_order_id' => $production_order_id,
                        'model_id' => $production_order_detail_item['model_id'],
                        'model_type' => $production_order_detail_item['model_type'],
                        'destination' => $production_order_detail_item['destination'],
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

    public function update_origin_curve(int $production_order_id, array $reassigned_details, array $selected_rows)
    {
        try {
            $production_order_origin = ProductionOrder::findOrFail($production_order_id);

            if (empty($selected_rows)) {
                return;
            }

            $reassigned_by_size = [];

            foreach ($reassigned_details as $detail) {

                if (($detail['model_type'] ?? null) !== Product::class) {
                    continue;
                }

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

                foreach ($selected_rows as $location) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $detail = $origin_details->first(function ($item) use ($location) {
                        return $item->destination === $location;
                    });

                    if (!$detail) {
                        continue;
                    }

                    $quantity = $detail->production_order_detail_quantities
                        ->firstWhere('size_id', $size_id);

                    if (!$quantity) {
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
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
