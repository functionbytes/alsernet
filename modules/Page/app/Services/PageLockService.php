<?php

namespace Modules\Page\Services;

use App\Models\User;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageLock;

class PageLockService
{
    /**
     * Adquirir o renovar un lock para una página
     */
    public function acquireLock(Page $page, User $user, ?string $sessionId = null): PageLock
    {
        return PageLock::acquireOrRenew($page, $user, $sessionId);
    }

    /**
     * Renovar un lock existente
     */
    public function renewLock(Page $page, User $user): ?PageLock
    {
        $lock = PageLock::getActiveLock($page);

        if (! $lock || ! $lock->isOwnedBy($user)) {
            return null;
        }

        return $lock->renew();
    }

    /**
     * Liberar un lock
     */
    public function releaseLock(Page $page, User $user): bool
    {
        $lock = PageLock::getActiveLock($page);

        if (! $lock || ! $lock->isOwnedBy($user)) {
            return false;
        }

        $lock->delete();

        return true;
    }

    /**
     * Obtener el lock activo de una página
     */
    public function getActiveLock(Page $page): ?PageLock
    {
        return PageLock::getActiveLock($page);
    }

    /**
     * Verificar si una página está bloqueada (por otro usuario)
     */
    public function isLockedByOther(Page $page, User $user): bool
    {
        $lock = PageLock::getActiveLock($page);

        if (! $lock) {
            return false;
        }

        return ! $lock->isOwnedBy($user);
    }

    /**
     * Obtener información del lock para enviar al frontend
     */
    public function getLockInfo(Page $page, User $user): ?array
    {
        $lock = $this->getActiveLock($page);

        if (! $lock) {
            return null;
        }

        return [
            'is_locked' => true,
            'is_owned_by_me' => $lock->isOwnedBy($user),
            'locked_by_user' => [
                'id' => $lock->user->id,
                'name' => $lock->user->full_name ?? $lock->user->name,
                'email' => $lock->user->email,
            ],
            'locked_at' => $lock->locked_at->diffForHumans(),
            'expires_in' => $lock->expires_at->diffInSeconds(now()),
            'expires_in_human' => $lock->expires_at->diffForHumans(),
        ];
    }

    /**
     * Limpiar todos los locks expirados
     */
    public function cleanExpiredLocks(): int
    {
        return PageLock::cleanExpired();
    }

    /**
     * Forzar la liberación de un lock (admin only)
     */
    public function forceReleaseLock(Page $page): bool
    {
        $lock = PageLock::getActiveLock($page);

        if (! $lock) {
            return false;
        }

        $lock->delete();

        return true;
    }
}
