<?php

namespace Modules\HelpdeskSocial\Observers;

use Modules\HelpdeskSocial\Models\SocialAgentWorkload;
use Modules\HelpdeskSocial\Models\SocialComment;

/**
 * Mantiene de forma atómica el contador `active_assigned_count` de
 * SocialAgentWorkload, evitando el COUNT(*) que SmartAssignmentService hacía
 * por cada asignación.
 *
 * Un comentario cuenta como "activo" para un agente cuando está asignado
 * (`assigned_to_user_id` no nulo) y su estado NO es final
 * (replied / closed / spam).
 */
class SocialCommentObserver
{
    /** @var array<int, string> */
    private const INACTIVE_STATUSES = ['replied', 'closed', 'spam'];

    public function created(SocialComment $comment): void
    {
        $userId = $this->userId($comment->assigned_to_user_id);

        if ($userId !== null && $this->isActiveStatus($comment->status)) {
            $this->increment($userId);
        }
    }

    public function updated(SocialComment $comment): void
    {
        if (! $comment->wasChanged(['assigned_to_user_id', 'status'])) {
            return;
        }

        $oldUserId = $this->userId($comment->getOriginal('assigned_to_user_id'));
        $newUserId = $this->userId($comment->assigned_to_user_id);

        $wasActive = $oldUserId !== null && $this->isActiveStatus($comment->getOriginal('status'));
        $isActive = $newUserId !== null && $this->isActiveStatus($comment->status);

        // Mismo agente: solo pudo cambiar el estado de actividad.
        if ($oldUserId === $newUserId) {
            if (! $wasActive && $isActive) {
                $this->increment($newUserId);
            } elseif ($wasActive && ! $isActive) {
                $this->decrement($oldUserId);
            }

            return;
        }

        // Reasignación: descuenta al anterior (si contaba) y suma al nuevo (si cuenta).
        if ($wasActive) {
            $this->decrement($oldUserId);
        }

        if ($isActive) {
            $this->increment($newUserId);
        }
    }

    public function deleted(SocialComment $comment): void
    {
        $userId = $this->userId($comment->assigned_to_user_id);

        if ($userId !== null && $this->isActiveStatus($comment->status)) {
            $this->decrement($userId);
        }
    }

    private function isActiveStatus(?string $status): bool
    {
        return ! in_array($status, self::INACTIVE_STATUSES, true);
    }

    private function userId(mixed $raw): ?int
    {
        return $raw !== null ? (int) $raw : null;
    }

    private function increment(int $userId): void
    {
        SocialAgentWorkload::query()
            ->firstOrCreate(['user_id' => $userId])
            ->increment('active_assigned_count');
    }

    private function decrement(int $userId): void
    {
        SocialAgentWorkload::query()
            ->where('user_id', $userId)
            ->where('active_assigned_count', '>', 0)
            ->decrement('active_assigned_count');
    }
}
