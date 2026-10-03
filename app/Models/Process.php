<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Process extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    public const ITEM_ID = 24;

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

    public const PENDING = 'Pendiente';

    public const REVIEW = 'En revision';

    public const APPROVED = 'Aprobado';

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

    public function after_processes(): MorphToMany
    {
        return $this->morphedByMany(Process::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function before_processes(): MorphToMany
    {
        return $this->morphToMany(Process::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function subprocesses(): MorphToMany
    {
        return $this->morphedByMany(Subprocess::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
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
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                $q->where(function (Builder $sq) use ($like) {
                    $sq->orWhere('name', 'LIKE', $like)
                        ->orWhere('description', 'LIKE', $like);
                });
            }
        });
    }

    public static function statuses()
    {
        return [
            self::PENDING,
            self::REVIEW,
            self::APPROVED,
        ];
    }
}
