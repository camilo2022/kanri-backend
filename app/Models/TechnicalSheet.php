<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class TechnicalSheet extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'technical_sheets';

    public const PENDING = 'Pendiente';

    public const REVIEW = 'En revision';

    public const APPROVED = 'Aprobado';

    public const CANCELLED = 'Cancelado';

    protected $fillable = [
        'model_id',
        'model_type',
        'code',
        'consecutive',
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
        'pattern_maker_id',
        'status',
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'code',
        'consecutive',
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
        'pattern_maker_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'physical_sample' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($technical_sheet) {
            if (empty($technical_sheet->uuid)) {
                $technical_sheet->uuid = Str::uuid();
            }
        });
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function derivatives()
    {
        return $this->morphMany(TechnicalSheet::class, 'model');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function products(): MorphToMany
    {
        return $this->morphToMany(Product::class, 'model', 'model_has_product', 'model_id', 'product_id');
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

    public function supplies(): MorphToMany
    {
        return $this->morphToMany(Supply::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id')->withTrashed();
    }

    public function technical_sheet_details(): HasMany
    {
        return $this->hasMany(TechnicalSheetDetail::class);
    }

    public function technical_sheet_details_processes(): HasMany
    {
        return $this->hasMany(TechnicalSheetDetail::class)
            ->where('model_type', Process::class);
    }

    public function production_orders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public static function statuses()
    {
        return [
            self::PENDING,
            self::REVIEW,
            self::APPROVED,
            self::CANCELLED,
        ];
    }
}
