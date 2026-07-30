<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialCrisisMode;

class CrisisModeService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * Check if the account is currently in crisis mode.
     */
    public function isInCrisisMode(SocialAccount $account): bool
    {
        return $account->isInCrisisMode();
    }

    /**
     * Log a crisis event for audit purposes.
     */
    public function logCrisisEvent(SocialAccount $account, string $event, array $context = []): void
    {
        Log::warning('CrisisModeService: crisis event', [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'event' => $event,
            'context' => $context,
        ]);
    }

    /**
     * Enter crisis mode for an account.
     */
    public function enterCrisisMode(SocialAccount $account, string $reason, int $userId): SocialCrisisMode
    {
        $this->logCrisisEvent($account, 'enter', ['reason' => $reason, 'user_id' => $userId]);
        $crisisMode = $account->enterCrisisMode($reason, $userId);
        $this->auditLog->log('crisis_mode_enter', $account, ['crisis_mode_active' => false], ['crisis_mode_active' => true, 'reason' => $reason]);

        return $crisisMode;
    }

    /**
     * Exit crisis mode for an account.
     */
    public function exitCrisisMode(SocialAccount $account): void
    {
        $this->logCrisisEvent($account, 'exit');
        $account->exitCrisisMode();
        $this->auditLog->log('crisis_mode_exit', $account, ['crisis_mode_active' => true], ['crisis_mode_active' => false]);
    }

    /**
     * Pause all outgoing activity for an account (auto-replies, syncs, replies).
     * This is a soft pause that can be checked before any outgoing operation.
     */
    public function pauseAllOutgoing(SocialAccount $account, ?string $reason = null): void
    {
        $account->update([
            'auto_reply_enabled' => false,
            'comments_enabled' => false,
            'messages_enabled' => false,
        ]);

        $this->logCrisisEvent($account, 'pause_all_outgoing', ['reason' => $reason]);
    }

    /**
     * Resume outgoing activity for an account.
     */
    public function resumeOutgoing(SocialAccount $account): void
    {
        $account->update([
            'auto_reply_enabled' => true,
            'comments_enabled' => true,
            'messages_enabled' => true,
        ]);

        $this->logCrisisEvent($account, 'resume_outgoing');
    }

    /**
     * Assert that an operation can proceed for the given account.
     * Throws if the account is in crisis mode.
     *
     * @throws \RuntimeException
     */
    public function assertCanProceed(SocialAccount $account, string $operation = 'sync'): void
    {
        if ($this->isInCrisisMode($account)) {
            $this->logCrisisEvent($account, 'blocked_operation', ['operation' => $operation]);

            throw new \RuntimeException(
                sprintf('Operation "%s" blocked: account "%s" is in crisis mode.', $operation, $account->name)
            );
        }
    }
}
