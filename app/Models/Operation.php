<?php

namespace App\Models;

use App\Casts\SettingsWithUuid;
use App\Traits\HasSettingsUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class Operation extends Model implements Auditable
{
    use Auditing, HasRoles, HasSettingsUuid, SoftDeletes;

    public const ITEM_ID = 26;

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
        'settings' => SettingsWithUuid::class,
    ];

    public function subprocess(): MorphToMany
    {
        return $this->morphToMany(Subprocess::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function after_operations(): MorphToMany
    {
        return $this->morphedByMany(Operation::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function before_operations(): MorphToMany
    {
        return $this->morphToMany(Operation::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function after_subprocesses(): MorphToMany
    {
        return $this->morphedByMany(Subprocess::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

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

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'workstation_id');
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        $terms = [preg_replace('/\s+/', ' ', $search)];

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
}
