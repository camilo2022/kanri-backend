<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class Rule extends Model implements Auditable
{
    use Auditing, HasRoles, SoftDeletes;

    public const ITEM_ID = 44;

    protected $table = 'subitems';

    protected $guard_name = 'api';

    protected $fillable = [
        'item_id',
        'name',
        'description',
        'settings',
    ];

    protected $auditInclude = [
        'item_id',
        'name',
        'description',
        'settings',
    ];

    protected $casts = [
        'settings' => 'object',
    ];

    protected static function booted()
    {
        static::addGlobalScope('item_id', function (Builder $builder) {
            $builder->where('item_id', self::ITEM_ID);
        });

        static::creating(function ($model) {
            $model->item_id = self::ITEM_ID;
        });

        static::updating(function ($model) {
            $model->item_id = self::ITEM_ID;
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function related(string $model): MorphToMany
    {
        return $this->morphedByMany($model, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    /**
     * Capacidades de la regla, de menos a más personas: [['people' => 3, 'time' => 40.0, 'time_unit' => 'min'], …].
     * Lee también el formato anterior (un solo people/time/time_unit en settings).
     */
    public function capacities(): array
    {
        $capacities = $this->settings->capacities ?? null;

        if ($capacities === null && isset($this->settings->time)) {
            $capacities = [(object) [
                'people' => $this->settings->people ?? 1,
                'time' => $this->settings->time,
                'time_unit' => $this->settings->time_unit ?? 'min',
            ]];
        }

        return collect($capacities ?? [])
            ->map(fn ($capacity) => [
                'people' => (int) ($capacity->people ?? 0),
                'time' => (float) ($capacity->time ?? 0),
                'time_unit' => ($capacity->time_unit ?? 'min') === 'seg' ? 'seg' : 'min',
            ])
            ->sortBy('people')
            ->values()
            ->all();
    }

    /**
     * Capacidad para un número exacto de personas, o null.
     */
    public function capacityFor(int $people): ?array
    {
        return collect($this->capacities())->firstWhere('people', $people);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }
         
        $terms = [preg_replace('/\s+/', ' ', $search)];

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                $q->where(function (Builder $sq) use ($like) {
                    $sq->orWhere('name', 'LIKE', $like)
                        ->orWhere('description', 'LIKE', $like);
                });
            }
        });
    }
}
