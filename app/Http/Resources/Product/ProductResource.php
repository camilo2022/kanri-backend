<?php

namespace App\Http\Resources\Product;

use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    use ModelTypes;

    public function toArray($request)
    {
        return [
            'product' => [
                'id' => $this->id,
                'code' => $this->code,
                'trademark_id' => $this->trademark_id,
                'subcategory_id' => $this->subcategory_id,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'trademark' => $this->trademark,
                'subcategory' => $this->subcategory,
            ],
            'status_technical_sheet' => TechnicalSheet::statuses(),
            'status_technical_sheet_detail' => TechnicalSheetDetail::statuses(),
            'model_types' => $this->getModelTypes(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
