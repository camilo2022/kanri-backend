<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class TechnicalSheet extends Model implements Auditable
{
    use Auditing;

    protected $table = 'technical_sheet';

    protected $fillable = [
        'product_id',
        'waistband_type_id',
        'boot_type_id',
        'yoke_type_id',
        'collection_id',
        'subgroup_id',
        'back_type_id',
        'garment_type_id',
        'silhouette_id',
        'wash_tone_id',
        'color_id',
    ];

    protected $auditInclude = [
        'product_id',
        'waistband_type_id',
        'boot_type_id',
        'yoke_type_id',
        'collection_id',
        'subgroup_id',
        'back_type_id',
        'garment_type_id',
        'silhouette_id',
        'wash_tone_id',
        'color_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function waistbandType(): BelongsTo
    {
        return $this->belongsTo(WaistbandType::class);
    }

    public function bootType(): BelongsTo
    {
        return $this->belongsTo(BootType::class);
    }

    public function yokeType(): BelongsTo
    {
        return $this->belongsTo(YokeType::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function subgroup(): BelongsTo
    {
        return $this->belongsTo(Subgroup::class);
    }

    public function backType(): BelongsTo
    {
        return $this->belongsTo(BackType::class);
    }

    public function garmentType(): BelongsTo
    {
        return $this->belongsTo(GarmentType::class);
    }

    public function silhouette(): BelongsTo
    {
        return $this->belongsTo(Silhouette::class);
    }

    public function washTone(): BelongsTo
    {
        return $this->belongsTo(WashTone::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
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
