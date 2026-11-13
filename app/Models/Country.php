<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Country extends Model implements Auditable
{
    use Auditing;

    protected $table = 'countries';

    protected $fillable = [
        'source_id',
        'region_id',
        'name',
        'iso2',
        'iso3',
        'phone_code',
        'settings',
    ];

    protected $auditInclude = [
        'settings',
    ];

    protected $casts = [
        'settings' => 'object',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function people(): MorphMany
    {
        return $this->morphMany(Person::class, 'location');
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'LIKE', '%'.$search.'%')
                ->orWhere('iso2', $search)
                ->orWhere('iso3', $search)
                ->orWhere('phone_code', ltrim($search, '+'));
        });
    }
}
