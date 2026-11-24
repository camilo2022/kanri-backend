<?php

namespace App\Models;

use App\Traits\SortableByRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class City extends Model
{
    use SortableByRelation;

    protected $table = 'cities';

    protected $fillable = [
        'source_id',
        'department_id',
        'name',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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

    public static function sortable_columns(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
        ];
    }
}
