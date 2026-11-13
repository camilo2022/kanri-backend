<?php

namespace App\Http\Resources\Location\Country;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'country' => [
                'id' => $this->id,
                'name' => $this->name,
                'iso2' => $this->iso2,
                'iso3' => $this->iso3,
                'phone_code' => $this->phone_code,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
