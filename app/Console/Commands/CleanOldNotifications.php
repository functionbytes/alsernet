<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanOldNotifications extends Command
{
    protected $signature = 'notifications:clean {--days=30 : Días de antigüedad para eliminar notificaciones}';
    protected $description = 'Limpiar notificaciones antiguas de la base de datos';

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Eliminando notificaciones anteriores a: {$cutoffDate->format('Y-m-d H:i:s')}");

        $deletedCount = DB::table('notifications')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $this->info("Se eliminaron {$deletedCount} notificaciones.");

        // También limpiar tokens inactivos
        $inactiveTokens = DB::table('push_notification_tokens')
            ->where('active', false)
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        $this->info("Se eliminaron {$inactiveTokens} tokens inactivos.");

        return 0;
    }
}