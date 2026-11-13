<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Employee extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'person_id',
        'operation_center',
        'position_id',
        'risk_manager_id',
        'health_entity_id',
        'pension_fund_id',
        'compensation_fund_id',
        'workstation_id',
        'start_date',
        'end_date',
    ];

    protected $auditInclude = [
        'person_id',
        'operation_center',
        'position_id',
        'risk_manager_id',
        'health_entity_id',
        'pension_fund_id',
        'compensation_fund_id',
        'workstation_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d H:i:s',
        'end_date' => 'date:Y-m-d H:i:s',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class)->withTrashed();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function risk_manager(): BelongsTo
    {
        return $this->belongsTo(RiskManager::class);
    }

    public function health_entity(): BelongsTo
    {
        return $this->belongsTo(HealthEntity::class);
    }

    public function pension_fund(): BelongsTo
    {
        return $this->belongsTo(PensionFund::class);
    }

    public function compensation_fund(): BelongsTo
    {
        return $this->belongsTo(CompensationFund::class);
    }

    public function workstation(): BelongsTo
    {
        return $this->belongsTo(Subitem::class, 'workstation_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'workstation_id');
    }

    public function subprocess(): BelongsTo
    {
        return $this->belongsTo(Subprocess::class, 'workstation_id');
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'workstation_id');
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
                    $sq->orWhereHas('person', function (Builder $personQuery) use ($term) {
                        $personQuery->where('document', 'LIKE', '%'.$term.'%')
                            ->orWhereRaw("CONCAT_WS(' ', names, last_names) LIKE ?", ['%'.$term.'%'])
                            ->orWhere('names', 'LIKE', '%'.$term.'%')
                            ->orWhere('last_names', 'LIKE', '%'.$term.'%');
                    })->orWhereHas('position', function (Builder $positionQuery) use ($term) {
                        $positionQuery->where('name', 'LIKE', "%{$term}%")
                            ->orWhereHas('area', function (Builder $areaQuery) use ($term) {
                                $areaQuery->where('name', 'LIKE', "%{$term}%");
                            });
                    })->orWhereHas('risk_manager', function (Builder $risk_managerQuery) use ($term) {
                        $risk_managerQuery->where('name', 'LIKE', "%{$term}%");
                    })->orWhereHas('health_entity', function (Builder $healthEntityQuery) use ($term) {
                        $healthEntityQuery->where('name', 'LIKE', "%{$term}%");
                    })->orWhereHas('workstation', function (Builder $workstationQuery) use ($term) {
                        $workstationQuery->where('name', 'LIKE', "%{$term}%");
                    });
                });
            }
        });
    }
}
