<?php

namespace App\Http\Resources\Audit;

use App\Support\AuditChanges;
use App\Support\AuditResolver;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AuditTimelineCollection extends ResourceCollection
{
    private const KEEP_WITHOUT_CHANGES = ['created', 'deleted', 'restored'];

    private const UNITS = 'Unidades';

    public function toArray($request)
    {
        $resolved = AuditResolver::forAudits($this->collection);
        $names = $this->recordNames();

        return [
            'audits' => $this->collection->map(function ($audit) use ($resolved, $names) {
                $first_click = $audit->getRelationValue('first_click');
                $changes = $first_click
                    ? [
                        'old' => [self::UNITS => AuditChanges::producedUnits($first_click->old_values)],
                        'new' => [self::UNITS => AuditChanges::producedUnits($audit->new_values)],
                    ]
                    : AuditChanges::of($audit, $resolved[$audit->id] ?? []);
                if (! $changes['old'] && ! $changes['new'] && ! in_array($audit->event, self::KEEP_WITHOUT_CHANGES, true)) {
                    return null;
                }
                $person = $audit->user?->employee?->person;

                return [
                    'id' => $audit->id,
                    'event' => $first_click ? 'units' : $audit->event,
                    'clicks' => $first_click ? $audit->clicks : null,
                    'started_at' => $first_click ? Carbon::parse($first_click->created_at)->format('Y-m-d H:i:s') : null,
                    'record' => $names[$audit->auditable_type][$audit->auditable_id] ?? null,
                    'user' => $person ? trim($person->names . ' ' . $person->last_names) : ($audit->user?->email ?? 'Sistema'),
                    'url' => $audit->url,
                    'ip_address' => $audit->ip_address,
                    'user_agent' => $audit->user_agent,
                    'old_values' => (object) $changes['old'],
                    'new_values' => (object) $changes['new'],
                    'created_at' => Carbon::parse($audit->created_at)->format('Y-m-d H:i:s'),
                ];
            })->filter()->values(),
            'meta' => [
                'pagination' => [
                    'total' => $this->total(),
                    'count' => $this->count(),
                    'per_page' => $this->perPage(),
                    'current_page' => $this->currentPage(),
                    'total_pages' => $this->lastPage(),
                ],
            ],
        ];
    }

    /** @return array<string, array<int, string>> tipo => [id => nombre del registro] */
    private function recordNames(): array
    {
        return $this->collection->groupBy('auditable_type')
            ->map(fn ($audits, $type) => AuditResolver::labelsOf($type, $audits->pluck('auditable_id')->unique()->values()->all()))
            ->all();
    }
}
