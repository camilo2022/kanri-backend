<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class MovementDetailQuantity extends Model implements Auditable
{
    use Auditing;

    protected $table = 'movement_detail_quantities';

    protected $fillable = [
        'movement_detail_id',
        'size_id',
        'quantity',
    ];

    protected $auditInclude = [
        'movement_detail_id',
        'size_id',
        'quantity',

    ];

    public function movement_detail(): BelongsTo
    {
        return $this->belongsTo(MovementDetail::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
