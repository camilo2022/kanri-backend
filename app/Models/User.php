<?php

namespace App\Models;

use App\Traits\SortableByRelation;
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
    use SortableByRelation;

    protected $table = 'users';

    protected $guard_name = 'api';

    protected $fillable = [
        'employee_id',
        'username',
        'password',
    ];

    protected $auditInclude = [
        'employee_id',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
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

    /**
     * Procesos, subprocesos y operaciones del usuario. En model_has_subitems el elemento del flujo va en subitem_id
     * (su llave foránea es a subitems) y el usuario en model_type/model_id.
     */
    public function processes(): MorphToMany
    {
        return $this->morphToMany(Process::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function subprocesses(): MorphToMany
    {
        return $this->morphToMany(Subprocess::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public function operations(): MorphToMany
    {
        return $this->morphToMany(Operation::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
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
                    $sq->orWhere('username', 'LIKE', '%'.ltrim($term, '@').'%')
                        ->orWhereHas('employee.person', function (Builder $employeeQuery) use ($term) {
                            $employeeQuery->where('names', 'LIKE', "%{$term}%")
                                ->orWhere('last_names', 'LIKE', "%{$term}%")
                                ->orWhereRaw("CONCAT_WS(' ', names, last_names) LIKE ?", ["%{$term}%"]);
                        });
                });
            }
        });
    }

    /** La auditoría deja constancia del cambio de contraseña, nunca su hash. */
    public function transformAudit(array $data): array
    {
        foreach (['old_values', 'new_values'] as $side) {
            if (isset($data[$side]['password'])) {
                $data[$side]['password'] = 'Cambiada';
            }
        }

        return $data;
    }

    public static function sortable_columns(): array
    {
        return [
            'id' => 'id',
            'username' => 'username',
            'name' => ['employee.person.names', 'employee.person.last_names'],
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
