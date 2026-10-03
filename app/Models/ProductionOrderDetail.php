<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class ProductionOrderDetail extends Model implements Auditable
{
    use Auditing;

    protected $table = 'production_order_details';

    protected $fillable = [
        'model_id',
        'model_type',
        'production_order_id',
        'destination',
        'settings'
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'production_order_id',
        'destination',
        'settings'
    ];

    protected $casts = [
        'settings' => 'object'
    ];

    public function production_order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function production_order_detail_quantities(): HasMany
    {
        return $this->hasMany(ProductionOrderDetailQuantities::class);
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
