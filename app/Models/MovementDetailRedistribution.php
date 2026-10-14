<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class MovementDetailRedistribution extends Model implements Auditable
{
    use Auditing;

    protected $table = 'movement_detail_redistributions';

    protected $fillable = [
        'movement_detail_id',
        'output_size_id',
        'input_size_id',
        'quantity',
    ];

    protected $auditInclude = [
        'movement_detail_id',
        'output_size_id',
        'input_size_id',
        'quantity',

    ];

    public function movement_detail(): BelongsTo
    {
        return $this->belongsTo(MovementDetail::class);
    }

    public function output_size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function input_size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
