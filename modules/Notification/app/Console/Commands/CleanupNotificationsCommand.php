<?php

namespace Modules\Notification\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class CleanupNotificationsCommand extends Command
{
    protected $signature = 'notifications:cleanup
                            {--days=30 : Días de antigüedad para eliminar notificaciones leídas}
                            {--read-only : Solo eliminar notificaciones leídas}';

    protected $description = 'Eliminar notificaciones antiguas de la base de datos';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $readOnly = (bool) $this->option('read-only');

        if ($days < 1) {
            $this->error('El número de días debe ser mayor a 0');

            return self::FAILURE;
        }

        $readDeleted = $this->deleteReadNotifications($days);
        $unreadDeleted = $readOnly ? 0 : $this->deleteUnreadNotifications();

        $this->info("Deleted {$readDeleted} read + {$unreadDeleted} unread notifications");

        return self::SUCCESS;
    }

    private function deleteReadNotifications(int $days): int
    {
        return DatabaseNotification::query()
            ->whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    private function deleteUnreadNotifications(): int
    {
        return DatabaseNotification::query()
            ->whereNull('read_at')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();
    }
}
