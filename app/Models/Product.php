<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Product extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'code',
        'subcategory_id',
        'trademark_id',
    ];

    protected $auditInclude = [
        'code',
        'subcategory_id',
        'trademark_id',
    ];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function trademark(): BelongsTo
    {
        return $this->belongsTo(Trademark::class);
    }

    public function technical_sheet(): HasOne
    {
        return $this->hasOne(TechnicalSheet::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->where('code', 'LIKE', "%{$term}%")
                        ->orWhereHas('trademark', function (Builder $trademarkQuery) use ($term) {
                            $trademarkQuery->where('name', 'LIKE', "%{$term}%");
                        })->orWhereHas('subcategory', function (Builder $subcategoryQuery) use ($term) {
                            $subcategoryQuery->where('name', 'LIKE', "%{$term}%");
                        });
                });
            }
        });
    }
}
