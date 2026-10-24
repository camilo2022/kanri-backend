<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Productivity extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    // Estado del turno según la hora actual
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

    /**
     * Paradas programadas del turno por model_has_subitems: la parada es el subítem (subitem_id) y el turno el
     * modelo (model_type = Productivity, model_id = turno). Las horas de cada parada están en settings.stops.
     */
    public function stops(): MorphToMany
    {
        return $this->morphToMany(Stop::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    /**
     * Registros de trabajo del turno: detalles de movimiento cuyo origen (model) es este turno.
     */
    public function movement_details(): MorphMany
    {
        return $this->morphMany(MovementDetail::class, 'model');
    }

    /**
     * Inicio del turno: su fecha con la hora de inicio.
     */
    public function startsAt(): Carbon
    {
        return Carbon::parse($this->date->format('Y-m-d').' '.$this->start_time);
    }

    /**
     * Fin del turno: su fecha con la hora de fin.
     */
    public function endsAt(): Carbon
    {
        return Carbon::parse($this->date->format('Y-m-d').' '.$this->end_time);
    }

    /**
     * Por iniciar, En curso o Terminado, según la hora actual.
     */
    public function status(): string
    {
        return match (true) {
            now()->lt($this->startsAt()) => self::PENDING,
            now()->lt($this->endsAt()) => self::IN_PROGRESS,
            default => self::FINISHED,
        };
    }

    /**
     * Settings que se muestran (meta por hora y paradas programadas), sin el trabajo en curso de los puestos.
     */
    public function publicSettings(): array
    {
        return [
            'goal_hour' => $this->settings->goal_hour ?? null,
            'stops' => $this->settings->stops ?? [],
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
}
