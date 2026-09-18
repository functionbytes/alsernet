<?php

namespace Modules\HelpdeskIntegration\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskIntegration\Models\IntegrationAuditLog;

class PurgeIntegrationAuditLogCommand extends Command
{
    protected $signature = 'helpdeskintegration:purge-audit-log
        {--days= : Dias a conservar (default: config helpdeskintegration.retention.audit_log_days)}
        {--dry-run : No eliminar, solo mostrar cuantos se eliminarian}';

    protected $description = 'Eliminar entradas del log de auditoria de integraciones antiguas segun la retencion configurada';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('helpdeskintegration.retention.audit_log_days', 180));

        if ($days <= 0) {
            $this->error('El parámetro days debe ser mayor que 0.');

            return self::INVALID;
        }

        $query = IntegrationAuditLog::query()->olderThan($days);
        $count = $query->count();

        if ($count === 0) {
            $this->info("No hay entradas de auditoría anteriores a {$days} días.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[dry-run] Se eliminarían {$count} entradas de auditoría anteriores a {$days} días.");

            return self::SUCCESS;
        }

        // Borrado en lotes (DELETE ... LIMIT), no una sola sentencia masiva:
        // con retención de 180 días y alto volumen (cada link/unlink/sync/
        // verificación de identidad crea una fila), un DELETE de cientos de
        // miles de filas de una vez bloquearía la tabla y con ella el INSERT
        // de auditoría concurrente que dispara cada acción de un agente.
        $deleted = 0;
        do {
            $affected = IntegrationAuditLog::query()->olderThan($days)->limit(1000)->delete();
            $deleted += $affected;
        } while ($affected > 0);

        $this->info("Eliminadas {$deleted} entradas de auditoría anteriores a {$days} días.");

        return self::SUCCESS;
    }
}
