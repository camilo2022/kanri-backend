<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movement\ProductionOrderDetail\ProductionOrderDetailAllRequest;
use App\Http\Resources\Movement\ProductionOrderDetail\ProductionOrderDetailCollection;
use App\Models\ProductionOrderDetail;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class ProductionOrderDetailController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(ProductionOrderDetailAllRequest $request)
    {
        try {
            $production_order_details = ProductionOrderDetail::with(['production_order', 'model' => ['trademark' => ['sizes']]])
                ->when($request->filled('production_order_detail_id'),
                    function ($query) use ($request) {
                        return $query->where('id', '!=', $request->input('production_order_detail_id'));
                    }
                )
                ->when($request->boolean('has_destination'),
                    function ($query) {
                        return $query->whereNotNull('destination');
                    }
                )
                ->get();

            return $this->successResponse(
                new ProductionOrderDetailCollection($production_order_details),
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
}
