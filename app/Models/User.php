<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    use Auditing, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $guard_name = 'api';

    protected $fillable = [
        'employee_id',
        'email',
        'password',
    ];

    protected $auditInclude = [
        'employee_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'email_verified_at',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->guard_name = 'api';
        });

        static::updating(function ($model) {
            $model->guard_name = 'api';
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function processes(): MorphToMany
    {
        return $this->morphedByMany(Process::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function subprocesses(): MorphToMany
    {
        return $this->morphedByMany(Subprocess::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
    }

    public function operations(): MorphToMany
    {
        return $this->morphedByMany(Operation::class, 'model', 'model_has_subitems', 'subitem_id', 'model_id');
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
                    $sq->orWhere('email', 'LIKE', '%'.$term.'%')
                        ->orWhereHas('employee.person', function (Builder $employeeQuery) use ($term) {
                            $employeeQuery->where('names', 'LIKE', "%{$term}%")
                                ->orWhere('last_names', 'LIKE', "%{$term}%");
                        });
                });
            }
        });
    }
}
