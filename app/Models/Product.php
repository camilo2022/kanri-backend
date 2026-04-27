<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Product extends Model implements Auditable
{
    use Auditing;

    protected $table = 'products';

    protected $fillable = [
        'referencia',
        'category_id',
        'subcategory_id',
        'trademark_id',
    ];

    protected $auditInclude = [
        'referencia',
        'category_id',
        'subcategory_id',
        'trademark_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function trademark(): BelongsTo
    {
        return $this->belongsTo(Trademark::class);
    }

    public function front_photo()
    {
        return $this->morphOne(File::class, 'model');
    }

    public function back_photo()
    {
        return $this->morphOne(File::class, 'model');
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                        ->orWhere('title', 'LIKE', '%' . $term . '%')
                        ->orWhere('description', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }
}
