<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Lo asociado a un registro de trabajo: empleados (Employee) y paradas (Stop). Las paradas guardan en settings su
 * tipo (programmed | unprogrammed), nombre e inicio y fin; una no programada abierta no tiene fin.
 */
class ProductivityDetailModel extends Model
{
    public const PROGRAMMED = 'programmed';

    public const UNPROGRAMMED = 'unprogrammed';

    protected $table = 'productivity_detail_models';

    protected $fillable = [
        'productivity_detail_id',
        'model_type',
        'model_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'object',
    ];

    public function productivity_detail(): BelongsTo
    {
        return $this->belongsTo(ProductivityDetail::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
