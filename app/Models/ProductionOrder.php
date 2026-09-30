<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class ProductionOrder extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'production_orders';

    public const PENDING = 'Pendiente';

    public const APPROVED = 'Aprobado';

    public const CANCELLED = 'Cancelado';

    public const NACIONAL = 'NACIONAL';

    public const MEDELLIN = 'MEDELLIN';

    public const STARA = 'STARA';

    public const PRIORITY_VERY_LOW = 1;

    public const PRIORITY_LOW = 2;

    public const PRIORITY_MEDIUM = 3;

    public const PRIORITY_HIGH = 4;

    public const PRIORITY_VERY_HIGH = 5;

    protected $fillable = [
        'technical_sheet_id',
        'model_id',
        'model_type',
        'consecutive',
        'production_place',
        'supplier_id',
        'efficiency',
        'cut',
        'fabric_id',
        'width',
        'pocket_fabric',
        'observation',
        'settings',
        'status',
    ];

    protected $auditInclude = [
        'technical_sheet_id',
        'model_id',
        'model_type',
        'consecutive',
        'production_place',
        'supplier_id',
        'efficiency',
        'cut',
        'fabric_id',
        'width',
        'pocket_fabric',
        'observation',
        'settings',
        'status',
    ];

    protected $casts = [
        'pocket_fabric' => 'boolean',
        'settings' => 'object',
        'date' => 'datetime',
    ];

    public function technical_sheet(): BelongsTo
    {
        return $this->belongsTo(TechnicalSheet::class);
    }

    public function derivatives()
    {
        return $this->morphMany(ProductionOrder::class, 'model');
    }

    public function color(): MorphToMany
    {
        return $this->morphedByMany(Color::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function fabric(): HasOne
    {
        return $this->hasOne(ProductionOrderDetail::class)->where('model_type', Supply::class);
    }

    public function production_order_details(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class);
    }

    public function rolls(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class)
            ->where('model_type', Supply::class)
            ->whereNull('destination')
            ->whereNotNull('settings->quantity')
            ->whereDoesntHave('production_order_detail_quantities');
    }

    public function pieces(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class)
            ->where('model_type', Piece::class);
    }

    public function reassignments(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class)
            ->where('model_type', ProductionOrder::class);
    }

    public function production_order_details_processes(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class)
            ->where('model_type', Process::class);
    }

    public function production_order_details_product(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class)
            ->where('model_type', Product::class);
    }

    public function strokes()
    {
        return $this->morphOne(File::class, 'model')
            ->where('file_type_id', 3)
            ->where('file_subtype_id', 13);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
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
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('consecutive', 'LIKE', '%'.$term.'%')
                        ->orWhere('cut', 'LIKE', '%'.$term.'%');
                });
            }
        });
    }

    public static function statuses()
    {
        return [
            self::PENDING,
            self::CANCELLED,
            self::APPROVED,
        ];
    }

    public static function destinations()
    {
        return [
            self::NACIONAL,
            self::MEDELLIN,
            self::STARA,
        ];
    }

    public static function priorities()
    {
        return [
            self::PRIORITY_VERY_LOW,
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_VERY_HIGH,
        ];
    }
}
