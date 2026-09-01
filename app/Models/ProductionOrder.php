<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class ProductionOrder extends Model implements Auditable
{
    use Auditing;

    protected $table = 'production_orders';

    public const PENDING = 'Pendiente';

    public const APPROVED = 'Aprobado';

    public const CANCELLED = 'Cancelado';

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

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function derivatives()
    {
        return $this->morphMany(ProductionOrder::class, 'model');
    }

    public function pieces(): MorphToMany
    {
        return $this->morphedByMany(Piece::class, 'model', 'model_has_production_order', 'production_order_id', 'model_id')->withPivot('quantity');
    }

    public function rolls(): MorphToMany
    {
        return $this->morphedByMany(Supply::class, 'model', 'model_has_production_order', 'production_order_id', 'model_id')->withPivot('quantity');
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

    public function strokes()
    {
        return $this->morphOne(File::class, 'model')
            ->where('file_type_id', 3)
            ->where('file_subtype_id', 13);
    }

    public function satellite(): BelongsTo
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
}
