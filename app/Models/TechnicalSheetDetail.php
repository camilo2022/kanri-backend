<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class TechnicalSheetDetail extends Model implements Auditable
{
    use Auditing;

    protected $table = 'technical_sheet_details';

    public const PENDING = 'Pendiente';
    public const REVIEW = 'En revision';
    public const APPROVED = 'Aprobado';

    protected $fillable = [
        'model_id',
        'model_type',
        'technical_sheet_id',
        'status',
        'settings'
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'technical_sheet_id',
        'status',
        'settings'
    ];

    protected $casts = [
        'settings' => 'object'
    ];

    public function technical_sheet(): BelongsTo
    {
        return $this->belongsTo(TechnicalSheet::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                        ->orWhere('title', 'LIKE', '%' . $term . '%')
                        ->orWhere('description', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }

    public static function statuses ()
    {
        return [
            self::PENDING,
            self::REVIEW,
            self::APPROVED
        ];
    }
}
