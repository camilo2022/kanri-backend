<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Person extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'document_type_id',
        'document',
        'names',
        'last_names',
        'email',
        'location_type',
        'location_id',
        'gender_id',
        'birth_date',
        'blood_type_id',
        'address',
        'neighborhood',
        'phone_country_id',
        'phone',
    ];

    protected $auditInclude = [
        'document_type_id',
        'document',
        'names',
        'last_names',
        'email',
        'location_type',
        'location_id',
        'gender_id',
        'birth_date',
        'blood_type_id',
        'address',
        'neighborhood',
        'phone_country_id',
        'phone',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
    ];

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function blood_type(): BelongsTo
    {
        return $this->belongsTo(BloodType::class);
    }

    public function photo(): MorphOne
    {
        return $this->morphOne(File::class, 'model');
    }

    public function phone_country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_country_id');
    }

    public function location(): MorphTo
    {
        return $this->morphTo();
    }

    public function document_type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function supplier(): MorphToMany
    {
        return $this->morphToMany(Supplier::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
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
                    $sq->orWhere('document', 'LIKE', '%'.$term.'%')
                        ->orWhere('names', 'LIKE', '%'.$term.'%')
                        ->orWhere('last_names', 'LIKE', '%'.$term.'%')
                        ->orWhereRaw("CONCAT_WS(' ', names, last_names) LIKE ?", ['%'.$term.'%'])
                        ->orWhere('birth_date', 'LIKE', '%'.$term.'%')
                        ->orWhere('phone', 'LIKE', '%'.$term.'%')
                        ->orWhere('email', 'LIKE', '%'.$term.'%')
                        ->orWhereHas('gender', function (Builder $genderQuery) use ($term) {
                            $genderQuery->where('description', 'LIKE', "%{$term}%");
                        })->orWhereHas('blood_type', function (Builder $bloodQuery) use ($term) {
                            $bloodQuery->where('name', 'LIKE', "%{$term}%");
                        });
                });
            }
        });
    }
}
