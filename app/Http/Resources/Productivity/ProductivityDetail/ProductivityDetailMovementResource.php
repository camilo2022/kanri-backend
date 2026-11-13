<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use App\Models\MovementDetail;
use App\Models\ProductivityDetail;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductivityDetailMovementResource extends JsonResource
{
    public function toArray($request)
    {
        $movement = $this->resource['movement'];
        $inputs = $movement->movement_details_input;
        $approved = $inputs->where('status', MovementDetail::APPROVED);
        $input_ids = $approved->pluck('id')->all();

        return [
            'measurement' => $movement->model->settings->productivity_measurement ?? null,
            'received' => (int) $approved->sum(fn ($detail) => $detail->movement_detail_quantities->sum('quantity')),
            'productivity_details' => $this->resource['productivity_details']->map(function (ProductivityDetail $productivity_detail) use ($inputs, $input_ids) {
                $produced = (array) ($productivity_detail->settings->produced ?? []);

                return [
                    'id' => $productivity_detail->id,
                    'productivity_id' => $productivity_detail->productivity_id,
                    'model_type' => $productivity_detail->model_type,
                    'model_id' => $productivity_detail->model_id,
                    'production_order_id' => $productivity_detail->production_order_id,
                    'rule_id' => $productivity_detail->rule_id,
                    'start' => $this->formatDate($productivity_detail->start),
                    'end' => $this->formatDate($productivity_detail->end),
                    'settings' => $productivity_detail->settings,
                    'created_at' => $this->formatDate($productivity_detail->created_at),
                    'updated_at' => $this->formatDate($productivity_detail->updated_at),
                    'productivity' => $productivity_detail->productivity,
                    'model' => $productivity_detail->model,
                    'production_order' => $productivity_detail->production_order,
                    'rule' => $productivity_detail->rule,
                    'employees' => $productivity_detail->employees,
                    'stops' => $productivity_detail->stops,
                    'movement_details' => $productivity_detail->movement_details,
                    // Unidades de este destino: cruza los +1 del registro con los ingresos del movimiento
                    'units' => match (true) {
                        isset($productivity_detail->settings->produced) => (int) collect($produced)->only($input_ids)->sum(),
                        $inputs->contains(fn ($input) => $input->model_type === ProductivityDetail::class && (int) $input->model_id === $productivity_detail->id) => (int) $inputs
                            ->where('model_type', ProductivityDetail::class)
                            ->where('model_id', $productivity_detail->id)
                            ->whereNotIn('status', [MovementDetail::DECLINED, MovementDetail::CANCELLED])
                            ->sum(fn ($input) => $input->movement_detail_quantities->sum('quantity')),
                        default => null,
                    },
                    'other_units' => isset($productivity_detail->settings->produced) ? (int) collect($produced)->except($input_ids)->sum() : null,
                ];
            })->values(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
