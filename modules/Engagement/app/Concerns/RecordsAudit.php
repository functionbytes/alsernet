<?php

namespace Modules\Engagement\Concerns;

use Modules\Engagement\Models\AuditLog;

trait RecordsAudit
{
    protected function audit(string $action, string $entityType, int $entityId, array $changes = []): void
    {
        AuditLog::record($action, $entityType, $entityId, $changes);
    }
}
