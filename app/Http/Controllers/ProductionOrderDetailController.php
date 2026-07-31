<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\ProductionOrderDetail\ProductionOrderDetailSaveRequest;
use App\Http\Requests\TechnicalSheetDetail\TechnicalSheetDetailSaveRequest;
use App\Http\Resources\Management\Collection\TechnicalSheetResource;
use App\Models\ProductionOrderDetail;
use App\Models\TechnicalSheetDetail;

use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class ProductionOrderDetailController extends Controller
{
    use ApiMessage, ApiResponser;

    public function save(ProductionOrderDetailSaveRequest $request)
    {
        foreach ($request->input('production_order_details', []) as $production_order_detail) {
            ProductionOrderDetail::updateOrCreate(
                [
                    'production_order_id' => $request->integer('id'),
                    'model_type' => $production_order_detail['model_type'],

                ],
                [
                    'model_id' => $production_order_detail['model_id'],
                    'destination' => $production_order_detail['destination'],
                ]
            );
        }
        return $this->successResponse(
            $this->getMessage('Success'),
            200
        );
    }
}
