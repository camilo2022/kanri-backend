<?php

namespace App\Http\Resources\Person;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PersonCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Person::class, $this->collection->pluck('id')->all());
        return [
            'people' => $this->collection->map(function ($person) use ($audited) {
                return [
                    'id' => $person->id,
                    'has_audits' => isset($audited[$person->id]),
                    'document' => $person->document,
                    'names' => $person->names,
                    'last_names' => $person->last_names,
                    'birth_date' => $person->birth_date,
                    'email' => $person->email,
                    'address' => $person->address,
                    'neighborhood' => $person->neighborhood,
                    'phone_country' => $person->phone_country,
                    'phone' => $person->phone,
                    'created_at' => $this->formatDate($person->created_at),
                    'updated_at' => $this->formatDate($person->updated_at),
                    'deleted_at' => $this->formatDate($person->deleted_at),
                    'gender' => $person->gender,
                    'photo' => $person->photo,
                    'blood_type' => $person->blood_type,
                    'employee' => $person->employee
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
