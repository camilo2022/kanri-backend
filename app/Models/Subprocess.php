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
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Subprocess extends Model implements Auditable
{
    use Auditing, HasSettingsUuid, SoftDeletes;

    public const ITEM_ID = 25;

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
        'settings' => SettingsWithUuid::class
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

    /**
     * Proceso del subproceso. No usar sync ni detach al editar: comparte model_type con before_subprocesses
     * (filas donde este subproceso es model_id) y los borraría.
     */
    public function process(): MorphToMany
    {
        return $this->morphToMany(Process::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function operations(): MorphToMany
    {
        return $this->morphedByMany(Operation::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    /**
     * Subprocesos siguientes del mismo proceso (cuando su flujo es en secuencia): este es subitem_id y el siguiente
     * model_id, igual que Process::after_processes.
     */
    public function after_subprocesses(): MorphToMany
    {
        return $this->morphedByMany(Subprocess::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function before_subprocesses(): MorphToMany
    {
        return $this->morphToMany(Subprocess::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    /**
     * Operaciones (de otros subprocesos del proceso) que lo tienen como siguiente: RESINA → LAVADO RESINA.
     */
    public function before_operations(): MorphToMany
    {
        return $this->morphToMany(Operation::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    /**
     * Empleados asignados a este puesto de trabajo.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'workstation_id');
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
}
