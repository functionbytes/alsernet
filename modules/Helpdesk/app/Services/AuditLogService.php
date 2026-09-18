<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Modules\Helpdesk\Models\AuditLog;

class AuditLogService
{
    /**
     * Log an auditable action.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function log(string $action, Model $entity, array $before = [], array $after = []): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'entity_type' => $entity->getMorphClass(),
                'entity_id' => $entity->getKey(),
                'before' => $before ?: null,
                'after' => $after ?: null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Audit failures must never break the main flow
            Log::warning('AuditLog write failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Static shortcut for inline usage.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public static function record(string $action, Model $entity, array $before = [], array $after = []): void
    {
        app(self::class)->log($action, $entity, $before, $after);
    }
}
