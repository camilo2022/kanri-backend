<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class BankAccount extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'model_id',
        'model_type',
        'bank_id',
        'account_type_id',
        'account_number',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function account_type()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function bank_certificate(): MorphOne
    {
        return $this->morphOne(File::class, 'model');
    }
}
