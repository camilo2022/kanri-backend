<?php

namespace App\Http\Resources\AccountType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AccountTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'account_types' => $this->collection->map(function ($account_type) {
                return [
                    'id' => $account_type->id,
                    'item_id' => $account_type->item_id,
                    'name' => $account_type->name,
                    'description' => $account_type->description,
                    'created_at' => $this->formatDate($account_type->created_at),
                    'updated_at' => $this->formatDate($account_type->updated_at),
                    'deleted_at' => $this->formatDate($account_type->deleted_at),
                    'settings' => $account_type->settings,
                    'item' => $account_type->item,
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
