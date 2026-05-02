<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Modules\HelpdeskSocial\Models\SocialAuditLog;

class AuditLogService
{
    public function log(string $action, Model $auditable, ?array $oldValues = null, ?array $newValues = null): SocialAuditLog
    {
        return SocialAuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
