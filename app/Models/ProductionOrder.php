<?php

namespace App\Models;

use App\Traits\SortableByRelation;
use App\Services\ProductivityDetailService;
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
    use SortableByRelation;

    protected $table = 'production_orders';

    public const PENDING = 'Pendiente';

    public const APPROVED = 'Aprobado';

    public const CANCELLED = 'Cancelado';

    public const NACIONAL = 'NACIONAL';

    public const MEDELLIN = 'MEDELLIN';

    public const STARA = 'STARA';

    public const BLESS = 'BLESS';

    public const SATELITE = 'SATELITE';

    public const PRIORITY_VERY_LOW = 1;

    public const PRIORITY_LOW = 2;

    public const PRIORITY_MEDIUM = 3;

    public const PRIORITY_HIGH = 4;

    public const PRIORITY_VERY_HIGH = 5;

    protected $fillable = [
        'technical_sheet_id',
        'consecutive',
        'production_place',
        'supplier_id',
        'efficiency',
        'cut',
        'width',
        'pocket_fabric',
        'observation',
        'settings',
        'status',
    ];

    protected $auditInclude = [
        'technical_sheet_id',
        'consecutive',
        'production_place',
        'supplier_id',
        'efficiency',
        'cut',
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

    public function derivatives(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class, 'model_id')
            ->where('model_type', ProductionOrder::class);
    }

    public function color(): MorphToMany
    {
        return $this->morphToMany(Color::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    /**
     * Tela de la orden: el detalle cuyo insumo es de un tipo marcado como tela (`settings.paragraph = fabric`). Los rollos
     * también son detalles de insumo, así que sin ese filtro MySQL podía devolver un rollo.
     */
    public function fabric(): HasOne
    {
        return $this->hasOne(ProductionOrderDetail::class)
            ->where('model_type', Supply::class)
            ->whereIn('model_id', Supply::withTrashed()
                ->whereHas('supply_type', fn (Builder $query) => $query->where('settings->paragraph', 'fabric'))
                ->select('subitems.id'));
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
            ->where('file_subtype_id', 15);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    /**
     * Lotes que la programación en cadena (CORTE) puede poner en el turno: los que tienen unidades en el proceso y les falta
     * pasar por algún puesto de la cadena que su ficha lleva, no solo por el primero (EXTENDIDO, CORTADO y ETIQUETADO hechos y
     * le falta REVISIÓN CORTE). El plan se salta los puestos ya hechos.
     */
    public function scopeChainPending(Builder $query, int $process_id): Builder
    {
        $process = Process::find($process_id);
        $service = app(ProductivityDetailService::class);
        $workstations = $service->workstations($process)->map(fn ($item) => $item['model_type']::find($item['model_id']))->filter();

        $ids = static::query()
            ->programmableIn($process_id)
            ->get()
            ->filter(fn (ProductionOrder $production_order) => $service->lotUnitsIn($production_order, $process) > 0
                && $workstations->contains(fn (Model $workstation) => ! $service->routeError($production_order, $workstation, $process)
                    && $service->lotWaiting($production_order, $workstation, $process)))
            ->pluck('id');

        return $query->whereIn('production_orders.id', $ids);
    }

    public function scopeProgrammableIn(Builder $query, int $process_id, ?Model $workstation = null, bool $pending_previous = false): Builder
    {
        $process = Process::find($process_id);
        $first = $process?->isFirst();

        // El lugar de producción decide la rama: un lote de satélite no pasa por la rama de la empresa y viceversa
        $query->whereNotIn('production_place', $process ? app(ProductivityDetailService::class)->excludedPlaces($process) : [])
            ->whereHas('technical_sheet.technical_sheet_details', fn ($detail) => $detail
            ->where('model_type', Process::class)
            ->where('model_id', $process_id)
            ->whereIn('status', [TechnicalSheetDetail::REVIEW, TechnicalSheetDetail::APPROVED]))
            // El primer proceso no recibe ingresos
            ->unless($first, fn ($lots) => $lots
                ->whereHas('production_order_details.movements', fn ($movement) => $movement
                    ->where('model_type', Process::class)
                    ->where('model_id', $process_id)
                    ->whereHas('movement_details_input', fn ($input) => $input->where('status', MovementDetail::APPROVED))));

        if (! $workstation) {
            return $query;
        }

        $service = app(ProductivityDetailService::class);
        $ids = static::query()
            ->programmableIn($process_id)
            ->get()
            ->filter(fn (ProductionOrder $production_order) => ! $service->routeError($production_order, $workstation, $process)
                // Con anteriores en la cadena, el lote debe estar programado o trabajado en ellos (Relojera tras Parche); la
                // programación los pide igual (pending_previous) y los muestra según lo que tenga puesto en el anterior
                && ($pending_previous || ! $service->previousScheduledError($production_order, $workstation, $process))
                // Por lote: el lote tiene unidades en el proceso (la curva con tallas activas o lo recibido) y le falta
                // pasar por el puesto; por unidades: le quedan unidades por hacer en su paso
                && ($service->schedulesByLot($process, $workstation)
                    ? $service->lotUnitsIn($production_order, $process) > 0 && $service->lotWaiting($production_order, $workstation, $process)
                    : $service->unitsToDo($production_order, $workstation, $process) > 0))
            ->pluck('id');

        return $query->whereIn('production_orders.id', $ids);
    }

    /**
     * Por qué no se puede poner fecha a este proceso del lote en Gestión de Producción, o null.
     */
    public static function planningError(int $technical_sheet_id, Process $process): ?string
    {
        $ready = TechnicalSheetDetail::where('technical_sheet_id', $technical_sheet_id)
            ->where('model_type', Process::class)
            ->where('model_id', $process->id)
            ->whereIn('status', [TechnicalSheetDetail::REVIEW, TechnicalSheetDetail::APPROVED])
            ->exists();

        return $ready ? null : "{$process->name} aún no está en revisión ni aprobado en la ficha técnica.";
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        $terms = [preg_replace('/\s+/', ' ', $search)];

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

    public static function sortable_columns(): array
    {
        return [
            'id' => 'id',
            'consecutive' => 'consecutive',
            'cut' => 'cut',
            'status' => 'status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
        ];
    }
}
