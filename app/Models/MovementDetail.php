<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;

class MovementDetail extends Model implements Auditable
{
    use Auditing;

    public const PENDING = 'Pendiente';
    public const DECLINED = 'Rechazado';
    public const APPROVED = 'Aprobado';
    public const CANCELLED = 'Cancelado';

    protected $table = 'movement_details';

    protected $fillable = [
        'movement_id',
        'model_type',
        'model_id',
        'status',
        'observation',
        'settings',
    ];

    protected $auditInclude = [
        'movement_id',
        'model_type',
        'model_id',
        'status',
        'observation',
        'settings',
    ];

    protected $casts = [
        'settings' => 'object',
    ];

    public function setUpdatedAt($value)
    {
        if ($this->exists) {
            parent::setUpdatedAt($value);
        }

        return $this;
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function movement_detail_redistributions(): HasMany
    {
        return $this->hasMany(MovementDetailRedistribution::class);
    }

    public function movement_detail_quantities(): HasMany
    {
        return $this->hasMany(MovementDetailQuantity::class)->whereNotNull('size_id');
    }

    public function counter(): HasOne
    {
        return $this->hasOne(MovementDetailQuantity::class)->whereNull('size_id');
    }

    public function rules(): MorphToMany
    {
        return $this->morphToMany(Rule::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function user_created(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Audit::class, 'auditable_id', 'id', 'id', 'user_id')
            ->where('audits.auditable_type', self::class)
            ->where('audits.event', 'created')
            ->where('audits.user_type', User::class);
    }

    public function user_updated(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Audit::class, 'auditable_id', 'id', 'id', 'user_id')
            ->where('audits.auditable_type', self::class)
            ->where('audits.event', 'updated')
            ->where('audits.user_type', User::class);
    }

    public static function statuses()
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::DECLINED,
            self::CANCELLED,
        ];
    }
}
