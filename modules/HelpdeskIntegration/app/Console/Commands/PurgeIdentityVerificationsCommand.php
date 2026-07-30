<?php

namespace Modules\HelpdeskIntegration\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;

class PurgeIdentityVerificationsCommand extends Command
{
    protected $signature = 'helpdeskintegration:purge-identity-verifications
        {--days= : Dias a conservar (default: config helpdeskintegration.retention.identity_verifications_days)}
        {--dry-run : No eliminar, solo mostrar cuantos se eliminarian}';

    protected $description = 'Eliminar codigos OTP de verificacion de identidad antiguos segun la retencion configurada';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('helpdeskintegration.retention.identity_verifications_days', 30));

        if ($days <= 0) {
            $this->error('El parámetro days debe ser mayor que 0.');

            return self::INVALID;
        }

        $query = CustomerIdentityVerification::query()->olderThan($days);
        $count = $query->count();

        if ($count === 0) {
            $this->info("No hay verificaciones de identidad anteriores a {$days} días.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[dry-run] Se eliminarían {$count} verificaciones de identidad anteriores a {$days} días.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Eliminadas {$deleted} verificaciones de identidad anteriores a {$days} días.");

        return self::SUCCESS;
    }
}
