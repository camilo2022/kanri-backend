<?php

namespace App\Models;

use App\Traits\SortableByRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
use Spatie\Permission\Traits\HasRoles;

class WaistbandType extends Model implements Auditable
{
    use Auditing, HasRoles, SoftDeletes;
    use SortableByRelation;

    public const ITEM_ID = 21;

    protected $table = 'subitems';

    protected $guard_name = 'api';

    protected $fillable = [
        'item_id',
        'name',
        'description',
        'settings'
    ];

    protected $auditInclude = [
        'item_id',
        'name',
        'description',
        'settings'
    ];

    protected $casts = [
        'settings' => 'object'
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

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;
         
        $terms = [preg_replace('/\s+/', ' ', $search)];

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
                $q->where(function (Builder $sq) use ($like) {
                    $sq->orWhere('name', 'LIKE', $like)
                        ->orWhere('description', 'LIKE', $like);
                });
            }
        });
    }

    public static function sortable_columns(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'description' => 'description',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
        ];
    }
}
