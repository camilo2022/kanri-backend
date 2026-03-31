<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Employee extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'person_id',
        'operation_center',
        'position_id',
        'arl_id',
        'eps_id',
        'pension_fund_id',
        'compensation_fund_id',
        'start_date',
        'end_date'
    ];

    protected $auditInclude = [
        'person_id',
        'operation_center',
        'position_id',
        'arl',
        'eps',
        'pension_fund',
        'compensation_fund',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d H:i:s',
        'end_date' => 'date:Y-m-d H:i:s'
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function arl(): BelongsTo
    {
        return $this->belongsTo(Arl::class);
    }

    public function eps(): BelongsTo
    {
        return $this->belongsTo(Eps::class);
    }

    public function pension_fund(): BelongsTo
    {
        return $this->belongsTo(PensionFund::class);
    }

    public function compensation_fund(): BelongsTo
    {
        return $this->belongsTo(CompensationFund::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhereHas('person', function (Builder $personQuery) use ($term) {
                        $personQuery->where('document', 'LIKE', '%' . $term . '%')
                            ->orWhere('names', 'LIKE', '%' . $term . '%')
                            ->orWhere('last_names', 'LIKE', '%' . $term . '%');
                    })->orWhereHas('position', function (Builder $positionQuery) use ($term) {
                        $positionQuery->where('name', 'LIKE', "%{$term}%")
                            ->orWhereHas('area', function (Builder $areaQuery) use ($term) {
                                $areaQuery->where('name', 'LIKE', "%{$term}%");
                            });
                    })->orWhereHas('arl', function (Builder $arlQuery) use ($term) {
                        $arlQuery->where('name', 'LIKE', "%{$term}%");
                    })->orWhereHas('eps', function (Builder $epsQuery) use ($term) {
                        $epsQuery->where('name', 'LIKE', "%{$term}%");
                    });
                });
            }
        });
    }
}
