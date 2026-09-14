<?php

namespace Modules\Mailer\Console\Commands;

use Illuminate\Console\Command;
use Modules\Mailer\Models\MailerEndpointLog;

class PurgeMailerLogsCommand extends Command
{
    protected $signature = 'mailer:purge-logs {--days= : Días a conservar (default: config mailer-module.retention.logs_days)} {--dry-run : No eliminar, solo mostrar cuántos se eliminarían}';

    protected $description = 'Eliminar MailerEndpointLog antiguos según la retención configurada';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('mailer-module.retention.logs_days', 90));

        if ($days <= 0) {
            $this->error('El parámetro days debe ser mayor que 0.');

            return self::INVALID;
        }

        $query = MailerEndpointLog::olderThan($days);
        $count = $query->count();

        if ($count === 0) {
            $this->info("No hay logs anteriores a {$days} días.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[dry-run] Se eliminarían {$count} logs anteriores a {$days} días.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Eliminados {$deleted} logs anteriores a {$days} días.");

        return self::SUCCESS;
    }
}
