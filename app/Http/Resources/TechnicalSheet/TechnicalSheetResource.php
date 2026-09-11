<?php

namespace App\Http\Resources\TechnicalSheet;

use App\Models\ProductionOrder;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalSheetResource extends JsonResource
{
    use ModelTypes;

    public function toArray($request)
    {
        return [
            'technical_sheet' => [
                'id' => $this->id,
                'model_id' => $this->model_id,
                'model_type' => $this->model_type,
                'code' => $this->code,
                'date' => $this->date,
                'product_id' => $this->product_id,
                'collection_id' => $this->collection_id,
                'garment_type_id' => $this->garment_type_id,
                'subgroup_id' => $this->subgroup_id,
                'wash_tone_id' => $this->wash_tone_id,
                'color_id' => $this->color_id,
                'waistband_type_id' => $this->waistband_type_id,
                'measure_of_waistband' => $this->measure_of_waistband,
                'boot_type_id' => $this->boot_type_id,
                'yoke_type_id' => $this->yoke_type_id,
                'back_type_id' => $this->back_type_id,
                'physical_sample' => $this->physical_sample,
                'number_of_buttons' => $this->number_of_buttons,
                'description' => $this->description,
                'observation' => $this->observation,
                'status' => $this->status,
                'pattern_maker_id' => $this->pattern_maker_id,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'technical_sheet_details' => $this->technical_sheet_details,
                'technical_sheet_details_processes' => $this->technical_sheet_details_processes,
                'supplies' => $this->supplies,
                'pattern_maker' => $this->pattern_maker,
                'product' => $this->product,
                'products' => $this->products->first(),
                'collection' => $this->collection,
                'garment_type' => $this->garment_type,
                'subgroup' => $this->subgroup,
                'wash_tone' => $this->wash_tone,
                'color' => $this->color,
                'waistband_type' => $this->waistband_type,
                'boot_type' => $this->boot_type,
                'yoke_type' => $this->yoke_type,
                'back_type' => $this->back_type,
                'photo_d' => $this->photo_d,
                'photo_t' => $this->photo_t,

            ],
            'status_technical_sheet' => TechnicalSheet::statuses(),
            'status_technical_sheet_detail' => TechnicalSheetDetail::statuses(),
            'status_production_order' => ProductionOrder::statuses(),
            'model_types' => $this->getModelTypes(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
