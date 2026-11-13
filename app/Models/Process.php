<?php

namespace App\Models;

use App\Casts\SettingsWithUuid;
use App\Traits\HasSettingsUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Process extends Model implements Auditable
{
    use Auditing, HasSettingsUuid, SoftDeletes;

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
        'settings' => SettingsWithUuid::class,
    ];

    public const PENDING = 'Pendiente';

    public const REVIEW = 'En revision';

    public const APPROVED = 'Aprobado';

    // Flujo de los hijos (settings.subprocess_flow del proceso y settings.operation_flow del subproceso)
    public const SEQUENCE = 'sequence';

    public const PARALLEL = 'parallel';

    public const FLOWS = [self::SEQUENCE, self::PARALLEL];

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

    public function typologies(): MorphToMany
    {
        return $this->morphToMany(Typology::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function subprocesses(): MorphToMany
    {
        return $this->morphedByMany(Subprocess::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function productivities(): MorphMany
    {
        return $this->morphMany(Productivity::class, 'model');
    }

    /**
     * Borra las cadenas (siguientes y anteriores) entre los subprocesos del proceso. Se usa cuando su flujo deja de
     * ser en secuencia.
     */
    public function clearSubprocessChains(): void
    {
        $ids = $this->subprocesses()->withTrashed()->pluck('subitems.id');
        $operation_ids = DB::table('model_has_subitems')
            ->where('model_type', Operation::class)
            ->whereIn('subitem_id', $ids)
            ->pluck('model_id');

        // Entre subprocesos y de una operación a un subproceso (RESINA → LAVADO RESINA)
        DB::table('model_has_subitems')
            ->where('model_type', Subprocess::class)
            ->whereIn('subitem_id', $ids->merge($operation_ids))
            ->whereIn('model_id', $ids)
            ->delete();
    }

    public function isFirst(): bool
    {
        return (bool) ($this->settings->in_production_order ?? false) && ! $this->before_processes()->exists();
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

    public static function statuses()
    {
        return [
            self::PENDING,
            self::REVIEW,
            self::APPROVED,
        ];
    }
}
