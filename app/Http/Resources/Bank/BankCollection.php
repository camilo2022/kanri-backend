<?php

namespace App\Http\Resources\Bank;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BankCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'banks' => $this->collection->map(function ($bank) {
                return [
                    'id' => $bank->id,
                    'item_id' => $bank->item_id,
                    'name' => $bank->name,
                    'description' => $bank->description,
                    'created_at' => $this->formatDate($bank->created_at),
                    'updated_at' => $this->formatDate($bank->updated_at),
                    'deleted_at' => $this->formatDate($bank->deleted_at),
                    'settings' => $bank->settings,
                    'item' => $bank->item,
                ];
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
