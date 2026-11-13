<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'source_id',
        'country_id',
        'name',
        'state_code',
        'type',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function people(): MorphMany
    {
        return $this->morphMany(Person::class, 'location');
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);

        return $search === '' ? $query : $query->where('name', 'LIKE', '%'.$search.'%');
    }
}
