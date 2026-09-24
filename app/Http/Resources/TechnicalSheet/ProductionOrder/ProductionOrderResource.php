<?php

namespace App\Http\Resources\TechnicalSheet\ProductionOrder;

use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProductionOrderResource extends JsonResource
{
    public function toArray($request)
    {

        $rolls = $this->rolls->map(function ($roll) {
            logger(['roll', $roll]);
            $relations = collect($roll->model->supply_type->first()?->settings?->form ?? [])
                ->where('type', 'selectdinamic')
                ->mapWithKeys(function ($field) use ($roll) {

                    $key = Str::lower(Str::camel(
                        class_basename($field->model)
                    ));

                    $query = $roll->model->related($field->model);

                    return [
                        /*$key => $field->cardinality === 'single'
                            ? $query->first()
                            : $query->get(),

                        $field->field => $field->cardinality === 'single'
                            ? $query->first()
                            : $query->get()->modelKeys(),*/

                        $field->field => $field->cardinality === 'single'
                                                        ? $query->first()
                                                        : $query->get(),
                    ];
                });
            $roll->setRawAttributes(array_merge(
                $roll->getAttributes(),
                $relations->all()
            ), true);

            return $roll;
        });

        return [
            'production_order' => [
                'id' => $this->id,
                'model_id' => $this->model_id,
                'model_type' => $this->model_type,
                'model' => $this->model,
                'technical_sheet_id' => $this->technical_sheet_id,
                'consecutive' => $this->consecutive,
                'efficiency' => $this->efficiency,
                'cut' => $this->cut,
                'width' => $this->width,
                'pocket_fabric' => $this->pocket_fabric,
                'observation' => $this->observation,
                'settings' => $this->settings,
                'status' => $this->status,
                'production_place' => $this->production_place,
                'supplier_id' => $this->supplier_id ?? null,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'technical_sheet' => $this->technical_sheet,
                'strokes' => $this->strokes,
                'production_order_details' => $this->production_order_details,
                'fabric' => $this->fabric,
                'color' => $this->color,
                'pieces' => $this->pieces,
                'rolls' => $rolls,
                'reassignments' => $this->reassignments,
            ],
            'status_production_order' => ProductionOrder::statuses(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function formatDateProductionOrder($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d') : null;
    }
}
