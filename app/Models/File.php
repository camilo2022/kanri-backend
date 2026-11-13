<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class File extends Model implements Auditable
{
    use Auditing;

    // Lado máximo en píxeles de una imagen: más grande no se puede abrir para optimizarla sin agotar la memoria
    public const MAX_IMAGE_SIDE = 6000;

    protected $table = 'files';

    protected $fillable = [
        'model_id',
        'model_type',
        'file_type_id',
        'file_subtype_id',
        'name',
        'path',
        'mime',
        'extension',
        'size',
        'metadata',
        'settings',
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'file_type_id',
        'file_subtype_id',
        'name',
        'path',
        'mime',
        'extension',
        'size',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'object',
    ];

    public function getPathAttribute($value): ?string
    {
        return $value ? url('storage/'.$value) : null;
    }

    public function getPdfPathAttribute()
    {
        return public_path('storage/'.$this->attributes['path']);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
