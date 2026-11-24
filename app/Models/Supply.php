<?php

namespace App\Models;

use App\Traits\SortableByRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class Supply extends Model implements Auditable
{
    use Auditing, HasRoles, SoftDeletes;
    use SortableByRelation;

    public const ITEM_ID = 29;

    // Subtipo FOTO INSUMO (tipo ARCHIVOS DE INSUMOS) de todas las fotos de insumos. Cambia si se renumeran los subitems
    public const PHOTO_FILE_SUBTYPE_ID = 18;

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
        'settings' => 'object',
    ];

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

    public function supply_type(): MorphToMany
    {
        return $this->morphToMany(SupplyType::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function technical_sheets(): MorphToMany
    {
        return $this->morphedByMany(TechnicalSheet::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function production_order_details(): MorphMany
    {
        return $this->morphMany(
            ProductionOrderDetail::class,
            'model'
        );
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function related(string $model): MorphToMany
    {
        return $this->morphedByMany($model, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }
         
        $terms = [preg_replace('/\s+/', ' ', $search)];

        // Además del nombre y la descripción, busca en los campos del formulario de los tipos de insumo: los datos escritos
        // (settings.values) y el nombre de los registros de los selectores (tela, proveedor, color…)
        $fields = static::form_fields();
        $values = $fields->whereIn('type', ['number', 'text', 'textarea', 'date'])->pluck('field')->unique();
        $models = $fields->where('type', 'selectdinamic')->pluck('model')->unique()->values()->all();

        return $query->where(function (Builder $q) use ($terms, $values, $models) {
            foreach ($terms as $term) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                $q->where(function (Builder $sq) use ($like, $values, $models) {
                    $sq->orWhere($this->qualifyColumn('name'), 'LIKE', $like)
                        ->orWhere($this->qualifyColumn('description'), 'LIKE', $like);

                    foreach ($values as $field) {
                        $sq->orWhereRaw(
                            'json_unquote(json_extract('.$this->qualifyColumn('settings').', ?)) collate utf8mb4_unicode_ci like ?',
                            ['$.values.'.$field, $like]
                        );
                    }

                    if ($models) {
                        $sq->orWhereExists(fn ($exists) => $exists->selectRaw('1')
                            ->from('model_has_subitems as search_link')
                            ->join('subitems as search_related', 'search_related.id', '=', 'search_link.model_id')
                            ->whereColumn('search_link.subitem_id', $this->qualifyColumn('id'))
                            ->whereIn('search_link.model_type', $models)
                            ->where('search_related.name', 'LIKE', $like));
                    }
                });
            }
        });
    }

    // Los campos de los formularios de los tipos de insumo (settings.form): de ahí salen las columnas que se pueden ordenar
    // y en las que busca el buscador, así un campo nuevo funciona sin tocar este modelo. Se consultan una vez por petición
    protected static ?Collection $form_fields = null;

    public static function form_fields(): Collection
    {
        return static::$form_fields ??= SupplyType::query()->pluck('settings')
            ->flatMap(fn ($settings) => $settings->form ?? [])
            ->filter(fn ($field) => isset($field->type, $field->field))
            ->values();
    }

    public static function sortable_columns(): array
    {
        $form_columns = static::form_fields()
            ->mapWithKeys(fn ($field) => match ($field->type) {
                'selectdinamic' => [$field->field => 'related:'.$field->model.'.name'],
                'number', 'text', 'textarea', 'date' => [$field->field => 'settings->values->'.$field->field],
                default => [],
            })
            ->all();

        return [
            'id' => 'id',
            'name' => 'name',
            'description' => 'description',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
        ] + $form_columns;
    }
}
