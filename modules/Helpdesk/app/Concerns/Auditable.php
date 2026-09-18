<?php

namespace Modules\Helpdesk\Concerns;

use Modules\Helpdesk\Services\AuditLogService;

/**
 * Automatically records create / update / delete events to helpdesk_audit_logs.
 *
 * Apply to: Conversation, ConversationItem, AgentSettings, etc.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            AuditLogService::record(
                static::auditEntityName().'.created',
                $model,
                [],
                $model->getAttributes()
            );
        });

        static::updated(function ($model) {
            AuditLogService::record(
                static::auditEntityName().'.updated',
                $model,
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function ($model) {
            AuditLogService::record(
                static::auditEntityName().'.deleted',
                $model,
                $model->getAttributes(),
                []
            );
        });
    }

    /**
     * Override in each model to customise the action prefix.
     * Default: class basename in dot-lowercase notation (e.g. "conversation").
     */
    protected static function auditEntityName(): string
    {
        return strtolower(class_basename(static::class));
    }
}
