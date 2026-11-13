<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class ProductivityDetail extends Model implements Auditable
{
    use Auditing;

    protected $table = 'productivity_details';

    protected $fillable = [
        'productivity_id',
        'model_type',
        'model_id',
        'production_order_id',
        'rule_id',
        'start',
        'end',
        'settings',
    ];

    protected $auditInclude = [
        'productivity_id',
        'model_type',
        'model_id',
        'production_order_id',
        'rule_id',
        'start',
        'end',
        'settings',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'settings' => 'object',
    ];

    /** La hora de cada unidad ya está en el registro: copiarla en cada +1 hace crecer la auditoría sin aportar nada. */
    public function transformAudit(array $data): array
    {
        foreach (['old_values', 'new_values'] as $side) {
            if (isset($data[$side]['settings'])) {
                $settings = json_decode(is_string($data[$side]['settings']) ? $data[$side]['settings'] : json_encode($data[$side]['settings']), true);
                unset($settings['units_at']);
                $data[$side]['settings'] = $settings;
            }
        }

        return $data;
    }

    public function productivity(): BelongsTo
    {
        return $this->belongsTo(Productivity::class)->withTrashed();
    }

    public function model(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function production_order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class)->withTrashed();
    }

    public function models(): HasMany
    {
        return $this->hasMany(ProductivityDetailModel::class);
    }

    public function employees(): MorphToMany
    {
        return $this->morphedByMany(Employee::class, 'model', 'productivity_detail_models', 'productivity_detail_id', 'model_id')
            ->withPivot('settings')
            ->withTimestamps();
    }

    public function stops(): HasMany
    {
        return $this->hasMany(ProductivityDetailModel::class)->where('model_type', Stop::class)->orderBy('id');
    }

    public function movement_details(): MorphMany
    {
        return $this->morphMany(MovementDetail::class, 'model');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereNull('end')->whereNotNull('settings->scanned_at');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNull('settings->scanned_at');
    }
}
