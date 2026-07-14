<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
use Illuminate\Database\Eloquent\Builder;

class ProductionOrder extends Model implements Auditable
{
    use Auditing;

    protected $table = 'production_orders';

    public const PENDING = 'Pendiente';
    public const APPROVED = 'Aprobado';
    public const CANCELLED = 'Cancelado';

    protected $fillable = [
        'technical_sheet_id',
        'date',
        'model_id',
        'model_type',
        'consecutive',
        'efficiency',
        'cut',
        'fabric_id',
        'width',
        'pocket_fabric',
        'observation',
        'settings',
        'status'
    ];

    protected $auditInclude = [
        'technical_sheet_id',
        'date',
        'model_id',
        'model_type',
        'consecutive',
        'efficiency',
        'cut',
        'fabric_id',
        'width',
        'pocket_fabric',
        'observation',
        'settings',
        'status'
    ];

    protected $casts = [
        'pocket_fabric' => 'boolean',
        'settings' => 'object'
    ];

    public function technical_sheet(): BelongsTo
    {
        return $this->belongsTo(TechnicalSheet::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('consecutive', 'LIKE', '%' . $term . '%')
                        ->orWhere('cut', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }

    public static function statuses()
    {
        return [
            self::PENDING,
            self::CANCELLED,
            self::APPROVED
        ];
    }
}
