<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Modules\HelpdeskSocial\Models\SocialAuditLog;

class AuditLogService
{
    /**
     * Campos que nunca deben persistirse en el log de auditoría: los
     * accessors de SocialAccount los descifran al serializar con toArray(),
     * lo que dejaba los tokens OAuth en claro en helpdesk_social_audit_logs.
     */
    private const REDACTED_KEYS = ['page_access_token', 'user_access_token'];

    public function log(string $action, Model $auditable, ?array $oldValues = null, ?array $newValues = null): SocialAuditLog
    {
        return SocialAuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach (self::REDACTED_KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }
}
