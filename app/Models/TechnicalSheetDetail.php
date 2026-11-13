<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

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
        'settings',
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'technical_sheet_id',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'object',
    ];

    public function technical_sheet(): BelongsTo
    {
        return $this->belongsTo(TechnicalSheet::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function typology(): MorphToMany
    {
        return $this->morphToMany(Typology::class, 'model', 'model_has_subitems', 'model_id', 'subitem_id');
    }

    public static function statuses()
    {
        return [
            self::PENDING,
            self::REVIEW,
            self::APPROVED,
        ];
    }
}
