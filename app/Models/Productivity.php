<?php

namespace App\Models;

use App\Traits\SortableByRelation;
use App\Services\ProductionPlanService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Productivity extends Model implements Auditable
{
    use Auditing, SoftDeletes;
    use SortableByRelation;

    public const PENDING = 'Por iniciar';

    public const IN_PROGRESS = 'En curso';

    public const FINISHED = 'Terminado';

    protected $table = 'productivity';

    protected $fillable = [
        'model_id',
        'model_type',
        'date',
        'start_time',
        'end_time',
        'settings',
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'date',
        'start_time',
        'end_time',
        'settings',
    ];

    protected $casts = [
        'date' => 'date',
        'settings' => 'object',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function productivity_details(): HasMany
    {
        return $this->hasMany(ProductivityDetail::class);
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse($this->date->format('Y-m-d').' '.$this->start_time);
    }

    public function endsAt(): Carbon
    {
        return Carbon::parse($this->date->format('Y-m-d').' '.$this->end_time);
    }

    public function publicSettings(): array
    {
        return [
            'goal_hour' => $this->settings->goal_hour ?? null,
        ];
    }

    protected function durationHours(): Attribute
    {
        return Attribute::get(fn () => Carbon::parse($this->start_time)->diffInHours(Carbon::parse($this->end_time)));
    }

    protected function goalDay(): Attribute
    {
        return Attribute::get(fn () => isset($this->settings->goal_hour)
            ? round($this->settings->goal_hour * $this->duration_hours)
            : null);
    }

    public function status(): Attribute
    {
        return Attribute::get(function () {
            return match (true) {
                now()->lt($this->startsAt()) => self::PENDING,
                now()->lt($this->endsAt()) => self::IN_PROGRESS,
                default => self::FINISHED,
            };
        });
    }

    /**
     * Lotes que Gestión de Producción tenía planeados para este proceso en el día del turno al terminar el turno (o ahora,
     * si no ha terminado), aunque después les cambiaran la fecha. Cada uno trae `plan_progress`: completo, parcial o sin
     * trabajar hasta ese día, con los puestos hechos y los que faltan.
     */
    public function plannedOrders(): \Illuminate\Support\Collection
    {
        if (! $this->model instanceof Process) {
            return collect();
        }

        $plan = app(ProductionPlanService::class);
        $date = $this->date->format('Y-m-d');

        return $plan->plannedOrders($this->model, $date, $this->endsAt()->min(now()))
            ->each(fn (ProductionOrder $production_order) => $production_order->setAttribute('plan_progress', $plan->progress($production_order, $this->model, $date)));
    }

    public static function sortable_columns(): array
    {
        return [
            'id' => 'id',
            'date' => 'date',
            'start_time' => 'start_time',
            'end_time' => 'end_time',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
