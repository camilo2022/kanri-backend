<?php

namespace App\Models;

use App\Traits\SortableByRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Product extends Model implements Auditable
{
    use Auditing, SoftDeletes;
    use SortableByRelation;

    protected $table = 'products';

    protected $fillable = [
        'code',
        'subcategory_id',
        'trademark_id',
    ];

    protected $auditInclude = [
        'code',
        'subcategory_id',
        'trademark_id',
    ];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function trademark(): BelongsTo
    {
        return $this->belongsTo(Trademark::class);
    }

    public function technical_sheet(): HasOne
    {
        return $this->hasOne(TechnicalSheet::class);
    }

    public function technical_sheets(): MorphToMany
    {
        return $this->morphedByMany(TechnicalSheet::class, 'model', 'model_has_product', 'product_id', 'model_id');
    }

    // Una columna por proceso (estado de su detalle en la ficha), armadas desde la tabla de procesos: un proceso nuevo se
    // puede ordenar sin tocar este modelo. Se consultan una vez por petición
    protected static ?array $process_sortable_columns = null;

    public static function sortable_columns(): array
    {
        static::$process_sortable_columns ??= Process::withTrashed()->pluck('id')
            ->mapWithKeys(fn ($id) => [
                'technical_sheet_detail_'.$id => 'technical_sheet.technical_sheet_details[model_type='.Process::class.',model_id='.$id.'].status',
            ])
            ->all();

        return static::$process_sortable_columns + [
            'id' => 'id',
            'code' => 'code',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
            'trademark' => 'trademark.name',
            'group' => 'trademark.group.name',
            'category' => 'subcategory.category.name',
            'subcategory' => 'subcategory.name',
            'technical_sheet_code' => 'technical_sheet.code',
            'technical_sheet_consecutive' => 'technical_sheet.consecutive',
            'technical_sheet_collection' => 'technical_sheet.collection.name',
            'technical_sheet_garment_type' => 'technical_sheet.garment_type.name',
            'technical_sheet_wash_tone' => 'technical_sheet.wash_tone.name',
            'technical_sheet_boot_type' => 'technical_sheet.boot_type.name',
            'technical_sheet_status' => 'technical_sheet.status',
        ];
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $words = collect(preg_split('/\s+/', trim((string) $search), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($word) => str_replace('-', '', $word))
            ->filter()
            ->values();

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '\\%_').'%';

            $like_any = function (Builder $q, Model $model, array $columns) use ($like) {
                return $q->where(function (Builder $group) use ($model, $columns, $like) {
                    foreach ($columns as $column) {
                        $wrapped = $group->getQuery()->getGrammar()->wrap($model->qualifyColumn($column));
                        $group->orWhereRaw("REPLACE(REPLACE({$wrapped}, '-', ''), ' ', '') LIKE ?", [$like]);
                    }
                });
            };

            $trademarks = Trademark::withTrashed()
                ->where(fn (Builder $q) => $like_any($q, new Trademark, ['name', 'description']))
                ->orWhereHas('group', fn (Builder $q) => $like_any($q, new Group, ['name', 'description']))
                ->select('id');

            $subcategories = Subcategory::withTrashed()
                ->where(fn (Builder $q) => $like_any($q, new Subcategory, ['name', 'description']))
                ->orWhereHas('category', fn (Builder $q) => $like_any($q, new Category, ['name', 'description']))
                ->select('id');

            $technical_sheets = TechnicalSheet::whereNotNull('product_id')
                ->where(function (Builder $q) use ($like_any) {
                    $like_any($q, new TechnicalSheet, ['code', 'consecutive', 'status']);
                    $q->orWhereHas('collection', fn (Builder $c) => $like_any($c, new Collection, ['name', 'description']))
                        ->orWhereHas('garment_type', fn (Builder $c) => $like_any($c, new GarmentType, ['name', 'description']))
                        ->orWhereHas('wash_tone', fn (Builder $c) => $like_any($c, new WashTone, ['name', 'description']))
                        ->orWhereHas('boot_type', fn (Builder $c) => $like_any($c, new BootType, ['name', 'description']));
                })
                ->select('product_id');

            $query->where(function (Builder $q) use ($like_any, $trademarks, $subcategories, $technical_sheets) {
                $like_any($q, new Product, ['code']);
                $q->orWhereIn('products.trademark_id', $trademarks)
                    ->orWhereIn('products.subcategory_id', $subcategories)
                    ->orWhereIn('products.id', $technical_sheets);
            });
        }

        return $query;
    }
}
