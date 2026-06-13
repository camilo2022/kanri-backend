<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
use Illuminate\Support\Str;

class TechnicalSheet extends Model implements Auditable
{
    use Auditing;

    protected $table = 'technical_sheets';

    public const PENDING = 'Pendiente';
    public const REVIEW = 'En revision';
    public const APPROVED = 'Aprobado';
    public const CANCELLED = 'Cancelado';

    protected $fillable = [
        'model_id',
        'model_type',
        'code',
        'date',
        'product_id',
        'collection_id',
        'garment_type_id',
        'subgroup_id',
        'wash_tone_id',
        'color_id',
        'waistband_type_id',
        'measure_of_waistband',
        'boot_type_id',
        'yoke_type_id',
        'back_type_id',
        'physical_sample',
        'number_of_buttons',
        'description',
        'observation',
        'status'
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'code',
        'date',
        'product_id',
        'collection_id',
        'garment_type_id',
        'subgroup_id',
        'wash_tone_id',
        'color_id',
        'waistband_type_id',
        'measure_of_waistband',
        'boot_type_id',
        'yoke_type_id',
        'back_type_id',
        'physical_sample',
        'number_of_buttons',
        'description',
        'observation',
        'status'
    ];

    protected static function booted()
    {
        static::creating(function ($technical_sheet) {
            if (empty($technical_sheet->uuid)) {
                $technical_sheet->uuid = Str::uuid();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function waistband_type(): BelongsTo
    {
        return $this->belongsTo(WaistbandType::class)->withTrashed();
    }

    public function boot_type(): BelongsTo
    {
        return $this->belongsTo(BootType::class)->withTrashed();
    }

    public function yoke_type(): BelongsTo
    {
        return $this->belongsTo(YokeType::class)->withTrashed();
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class)->withTrashed();
    }

    public function subgroup(): BelongsTo
    {
        return $this->belongsTo(Subgroup::class)->withTrashed();
    }

    public function back_type(): BelongsTo
    {
        return $this->belongsTo(BackType::class)->withTrashed();
    }

    public function garment_type(): BelongsTo
    {
        return $this->belongsTo(GarmentType::class)->withTrashed();
    }

    public function wash_tone(): BelongsTo
    {
        return $this->belongsTo(WashTone::class)->withTrashed();
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class)->withTrashed();
    }

    public function photo_d()
    {
        return $this->morphOne(File::class, 'model')
            ->where('file_type_id', 3)
            ->where('file_subtype_id', 9)
            ->where('name', 'like', '%-D');
    }

    public function photo_t()
    {
        return $this->morphOne(File::class, 'model')
            ->where('file_type_id', 3)
            ->where('file_subtype_id', 9)
            ->where('name', 'like', '%-T');
    }

    public function pattern_maker(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pattern_maker_id')->withTrashed();
    }

    public function variants(): MorphToMany
    {
        return $this->morphedByMany(Variant::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id')->withTrashed();
    }

    public function technical_sheet_details(): HasMany
    {
        return $this->hasMany(TechnicalSheetDetail::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                        ->orWhere('title', 'LIKE', '%' . $term . '%')
                        ->orWhere('description', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }

    public static function statuses ()
    {
        return [
            self::PENDING,
            self::REVIEW,
            self::APPROVED,
            self::CANCELLED
        ];
    }
}
