<?php

namespace App\Http\Resources\Person;

use App\Services\PersonService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'person' => [
                'id' => $this->id,
                'document' => $this->document,
                'names' => $this->names,
                'last_names' => $this->last_names,
                'email' => $this->email,
                'birth_date' => $this->birth_date?->format('Y-m-d'),
                'location_type' => array_search($this->location_type, PersonService::LOCATION_TYPES, true) ?: null,
                'location' => $this->location,
                'address' => $this->address,
                'neighborhood' => $this->neighborhood,
                'phone_country' => $this->phone_country,
                'phone' => $this->phone,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'gender' => $this->gender,
                'photo' => $this->photo,
                'blood_type' => $this->blood_type,
                'document_type' => $this->document_type,
                'employee' => $this->employee,
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
