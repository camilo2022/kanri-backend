<?php

namespace App\Http\Resources\Typification\SupplyType;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplyTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\SupplyType::class, $this->collection->pluck('id')->all());
        return [
            'supply_types' => $this->collection->map(function ($supply_type) use ($audited) {
                $data = [
                    'id' => $supply_type->id,
                    'has_audits' => isset($audited[$supply_type->id]),
                    'item' => $supply_type->item,
                    'name' => $supply_type->name,
                    'description' => $supply_type->description,
                    'created_at' => $this->formatDate($supply_type->created_at),
                    'updated_at' => $this->formatDate($supply_type->updated_at),
                    'deleted_at' => $this->formatDate($supply_type->deleted_at),
                    'settings' => $supply_type->settings,
                ];

                // Solo si la consulta los cargó; leerlos sin cargar haría una consulta por tipo
                if ($supply_type->relationLoaded('supplies')) {
                    $data['supplies'] = $supply_type->supplies;
                }

                return $data;
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function paginationMeta()
    {
        return [
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
        ];
    }
}
