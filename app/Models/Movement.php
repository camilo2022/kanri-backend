<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Movement extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'movements';

    protected $fillable = [
        'model_id',
        'model_type',
        'production_order_detail_id',
        'status',
        'settings',
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'production_order_detail_id',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'object',
    ];

    public function production_order_detail(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderDetail::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {

            foreach ($terms as $term) {

                $q->where(function (Builder $sq) use ($term) {

                    $sq->where('consecutive', 'LIKE', "%{$term}%")
                        ->orWhere('cut', 'LIKE', "%{$term}%")

                        // MARCA
                        ->orWhereHas('model', function (Builder $query) use ($term) {
                            $query->where('name', 'LIKE', "%{$term}%");
                        })


                        // MARCA
                        ->orWhereHas('model.trademark', function (Builder $query) use ($term) {
                            $query->where('name', 'LIKE', "%{$term}%");
                        })

                        // COLECCIÓN
                        ->orWhereHas('model.technical_sheet.collection', function (Builder $query) use ($term) {
                            $query->where('name', 'LIKE', "%{$term}%");
                        })

                        // TIPO DE PRENDA
                        ->orWhereHas('model.technical_sheet.product.subcategory', function (Builder $query) use ($term) {
                            $query->where('name', 'LIKE', "%{$term}%");
                        })

                        // POSICIÓN
                        ->orWhereHas('position', function (Builder $query) use ($term) {
                            $query->where('name', 'LIKE', "%{$term}%")
                                ->orWhereHas('area', function (Builder $areaQuery) use ($term) {
                                    $areaQuery->where('name', 'LIKE', "%{$term}%");
                                });
                        });
                });
            }
        });
    }
}
