<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'engagement_audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'changes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function record(string $action, string $entityType, int $entityId, array $changes = []): void
    {
        try {
            self::query()->create([
                'user_id' => auth()->id(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'changes' => $changes,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable) {
            // audit log failures should never break the main action
        }
    }
}
