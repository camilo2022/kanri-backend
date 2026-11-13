<?php

namespace App\Http\Resources\Location\Country;

use App\Models\Country;
use App\Support\AuditResolver;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CountryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(Country::class, $this->collection->pluck('id')->all());

        return [
            'countries' => $this->collection->map(function ($country) use ($audited) {
                return [
                    'id' => $country->id,
                    'has_audits' => isset($audited[$country->id]),
                    'name' => $country->name,
                    'iso2' => $country->iso2,
                    'iso3' => $country->iso3,
                    'phone_code' => $country->phone_code,
                    'settings' => $country->settings,
                    'created_at' => $this->formatDate($country->created_at),
                    'updated_at' => $this->formatDate($country->updated_at),
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
