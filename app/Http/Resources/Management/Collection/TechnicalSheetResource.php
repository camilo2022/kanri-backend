<?php

namespace App\Http\Resources\Management\Collection;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalSheetResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'technical_sheet' => [
                'id' => $this->id,
                'uuid' => $this->uuid,
                'code' => $this->code,
                'date' => $this->date,
                'product_id' => $this->product_id,
                'collection_id' =>  $this->collection_id,
                'garment_type_id' =>  $this->garment_type_id,
                'subgroup_id' =>  $this->subgroup_id,
                'wash_tone_id' =>  $this->wash_tone_id,
                'color_id' =>  $this->color_id,
                'waistband_type_id' =>  $this->waistband_type_id,
                'measure_of_waistband' =>  $this->measure_of_waistband,
                'boot_type_id' =>  $this->boot_type_id,
                'yoke_type_id' =>  $this->yoke_type_id,
                'back_type_id' =>  $this->back_type_id,
                'physical_sample' =>  $this->physical_sample,
                'number_of_buttons' =>  $this->number_of_buttons,
                'description' => $this->description,
                'observation' =>  $this->observation,
                'status' =>  $this->status,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'product' => $this->product,
                'supplies' => $this->supplies,
                'collection' =>  $this->collection,
                'technical_sheet_details' => $this->technical_sheet_details,
                'garment_type' => $this->garment_type,
                'wash_tone' => $this->wash_tone,
                'boot_type' => $this->boot_type,
                'photo_d' => $this->photo_d,
                'photo_t' => $this->photo_t,
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
