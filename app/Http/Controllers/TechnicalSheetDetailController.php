<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheetDetail\TechnicalSheetDetailSaveRequest;
use App\Http\Resources\Management\Collection\TechnicalSheetResource;
use App\Models\TechnicalSheetDetail;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class TechnicalSheetDetailController extends Controller
{
    use ApiMessage, ApiResponser;

    public function save(TechnicalSheetDetailSaveRequest $request)
    {
        foreach ($request->input('technical_sheet_details', []) as $technical_sheet_detail) {
            TechnicalSheetDetail::updateOrCreate(
                [
                    'technical_sheet_id' => $technical_sheet_detail->technical_sheet_id,
                    'model_type' => $technical_sheet_detail['model_type'],
                    'model_id' => $technical_sheet_detail['model_id'],
                ],
                [
                    'status' => $technical_sheet_detail['status'],
                    'settings' => $technical_sheet_detail['settings'] ?? (object) [],
                ]
            );
        }

        return $this->successResponse(
            $this->getMessage('Success'),
            200
        );
    }
}
