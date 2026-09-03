<?php

namespace Modules\HelpdeskEmailActivity\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\ProviderWebhookEvent;

class PruneEmailLogsCommand extends Command
{
    protected $signature = 'email-logs:prune
        {--days= : Override the configured retention period in days}
        {--stale-hours= : Override the configured "stale queued" threshold in hours}
        {--trash-days= : Override the configured trash retention period in days}
        {--webhook-events-days= : Override the configured provider webhook event retention period in days}';

    protected $description = 'Delete old email log entries and mark stale queued entries as failed';

    public function handle(): int
    {
        $this->markStaleQueuedAsFailed();
        $this->deleteOldEntries();
        $this->pruneTrash();
        $this->pruneProviderWebhookEvents();

        return self::SUCCESS;
    }

    private function markStaleQueuedAsFailed(): void
    {
        $hours = (int) ($this->option('stale-hours') ?? Setting::get('helpdeskemailactivity.stale_queued_hours', config('helpdeskemailactivity.stale_queued_hours', 0)));

        if ($hours <= 0) {
            return;
        }

        $marked = EmailLog::query()
            ->staleQueued($hours)
            ->update([
                'status' => EmailStatus::Failed->value,
                'failed_at' => now(),
                'error_message' => "No sending confirmation received within {$hours}h.",
            ]);

        if ($marked > 0) {
            EmailLog::forgetDashboardCaches();
            $this->components->info("Marked {$marked} stale queued entr".($marked === 1 ? 'y' : 'ies').' as failed.');
        }
    }

    /**
     * Retención general (por antigüedad de created_at) — DECISIÓN: se
     * mantiene exactamente el mismo comportamiento de siempre, un borrado
     * DIRECTO y definitivo (forceDelete), sin pasar por la papelera. Dos
     * motivos:
     *
     *  1. No cambiar semántica ya probada: antes de la papelera (ver
     *     EmailLog::class, SoftDeletes), este método ya eliminaba estas filas
     *     para siempre — los tests existentes (test_old_entries_are_deleted_
     *     after_retention_window) aseveran justamente eso con
     *     assertDatabaseMissing().
     *  2. retention_days (90 días por defecto) es MUCHO más largo que
     *     trash_retention_days (30 días) — para cuando una fila llega aquí ya
     *     ha sobrevivido de sobra cualquier ventana de arrepentimiento
     *     razonable; añadirle encima 30 días más de papelera solo alargaría
     *     la retención real del sistema sin que nadie lo haya pedido.
     *
     * withTrashed() a propósito: una fila que un agente ya movió a la
     * papelera manualmente (destroy()/bulkDestroy()) pero que ADEMÁS ya
     * superó la retención general por su created_at no debe sobrevivir solo
     * porque todavía le quedaran días de papelera — retention_days es el
     * límite superior de todo el histórico, papelera incluida.
     */
    private function deleteOldEntries(): void
    {
        $days = (int) ($this->option('days') ?? Setting::get('helpdeskemailactivity.retention_days', config('helpdeskemailactivity.retention_days', 0)));

        if ($days <= 0) {
            $this->components->info('Retention is disabled (retention_days <= 0); nothing pruned.');

            return;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $deleted = EmailLog::withTrashed()
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->forceDelete();

            $total += $deleted;
        } while ($deleted > 0);

        if ($total > 0) {
            EmailLog::forgetDashboardCaches();
        }

        $this->components->info("Pruned {$total} email log entr".($total === 1 ? 'y' : 'ies')." older than {$days} days.");
    }

    /**
     * Purga de la papelera propiamente dicha: borra de forma DEFINITIVA
     * (forceDelete) los registros que un agente ya movió a la papelera
     * (destroy()/bulkDestroy(), ver EmailLogController) hace más de
     * `trash_retention_days` días. Nunca toca filas que siguen visibles en
     * el listado normal (onlyTrashed()) ni compite con deleteOldEntries():
     * esta sí borra por deleted_at, la otra por created_at (ver su docblock
     * para la decisión de por qué no comparten ventana).
     */
    private function pruneTrash(): void
    {
        $days = (int) ($this->option('trash-days') ?? Setting::get('helpdeskemailactivity.trash_retention_days', config('helpdeskemailactivity.trash_retention_days', 30)));

        if ($days <= 0) {
            $this->components->info('Trash retention is disabled (trash_retention_days <= 0); nothing purged from trash.');

            return;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $purged = EmailLog::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->limit(1000)
                ->forceDelete();

            $total += $purged;
        } while ($purged > 0);

        if ($total > 0) {
            EmailLog::forgetDashboardCaches();
        }

        $this->components->info("Purged {$total} email log entr".($total === 1 ? 'y' : 'ies')." from the trash (older than {$days} days).");
    }

    /**
     * Purga de email_provider_events (auditoría de webhooks de proveedor,
     * ver ProviderWebhookEvent::class) — independiente de retention_days/
     * trash_retention_days: estos eventos no son el email en sí, son el
     * registro de que un webhook llegó. Borrado directo (delete, no hay
     * papelera para esta tabla ni caché de dashboard que invalidar).
     */
    private function pruneProviderWebhookEvents(): void
    {
        $days = (int) ($this->option('webhook-events-days') ?? Setting::get('helpdeskemailactivity.webhook_events_retention_days', config('helpdeskemailactivity.webhook_events_retention_days', 30)));

        if ($days <= 0) {
            $this->components->info('Provider webhook event retention is disabled (webhook_events_retention_days <= 0); nothing pruned.');

            return;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $deleted = ProviderWebhookEvent::query()
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        $this->components->info("Pruned {$total} provider webhook event".($total === 1 ? '' : 's')." older than {$days} days.");
    }
}
