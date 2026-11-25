<?php

namespace App\Support;

use OwenIt\Auditing\Contracts\Audit;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Drivers\Database;

class AuditDatabaseDriver extends Database
{
    public function audit(Auditable $model): ?Audit
    {
        return $model->withSavepointIfNeeded(
            fn () => $model->audits()->getModel()::create([...$model->toAudit(), 'origin' => AuditOrigin::current()])
        );
    }
}
