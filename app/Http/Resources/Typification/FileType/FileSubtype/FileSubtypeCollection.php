<?php

namespace App\Http\Resources\Typification\FileType\FileSubtype;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FileSubtypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'file_subtypes' => $this->collection->map(function ($file_subtype) {
                return [
                    'id' => $file_subtype->id,
                    'item' => $file_subtype->item,
                    'name' => $file_subtype->name,
                    'description' => $file_subtype->description,
                    'created_at' => $this->formatDate($file_subtype->created_at),
                    'updated_at' => $this->formatDate($file_subtype->updated_at),
                    'deleted_at' => $this->formatDate($file_subtype->deleted_at),
                    'settings' => $file_subtype->settings,
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
