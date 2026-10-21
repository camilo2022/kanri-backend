<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
        return $this->hasMany(MovementDetailQuantity::class);
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
