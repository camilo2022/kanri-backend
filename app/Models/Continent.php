<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Continent extends Model
{
    protected $table = 'continents';

    protected $fillable = [
        'source_id',
        'name',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }
}
