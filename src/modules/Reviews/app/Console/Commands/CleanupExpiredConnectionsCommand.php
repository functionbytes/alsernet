<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Notifications\ConnectionExpiringNotification;

class CleanupExpiredConnectionsCommand extends Command
{
    protected $signature = 'reviews:cleanup-expired';

    protected $description = 'Marcar conexiones OAuth expiradas y notificar propietarios';

    public function handle(): int
    {
        $this->info('Buscando conexiones expiradas...');
        $this->newLine();

        $expiredConnections = ReviewGoogleConnection::query()
            ->where('status', ConnectionStatus::ACTIVE)
            ->where('token_expires_at', '<', now())
            ->get();

        if ($expiredConnections->isEmpty()) {
            $this->info('No se encontraron conexiones expiradas');

            return self::SUCCESS;
        }

        $this->info("Se encontraron {$expiredConnections->count()} conexiones expiradas");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($expiredConnections->count());
        $progressBar->start();

        $marked = 0;

        foreach ($expiredConnections as $connection) {
            try {
                $connection->markAsExpired('Token expirado automáticamente');
                $this->notifyOwner($connection);
                $marked++;
            } catch (\Exception $e) {
                $this->error("Error procesando conexión {$connection->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        $this->info('Resumen de limpieza:');
        $this->line("✅ Conexiones marcadas como expiradas: $marked");

        return self::SUCCESS;
    }

    private function notifyOwner(ReviewGoogleConnection $connection): void
    {
        $connection->loadMissing('user');

        if ($connection->user) {
            $connection->user->notify(new ConnectionExpiringNotification($connection, 0));
        }

        $this->line("Conexión '{$connection->name}' marcada como expirada");
    }
}
