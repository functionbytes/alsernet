<?php

namespace Modules\HelpdeskEmailActivity\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskEmailActivity\Notifications\BounceProcessingFailedNotification;
use Modules\HelpdeskEmailActivity\Services\BounceMailboxesRepository;
use Modules\HelpdeskEmailActivity\Services\BounceProcessorService;
use Throwable;

/**
 * Revisa todos los buzones de rebote habilitados (ver
 * BounceMailboxesRepository) y actualiza a 'bounced'/'complained' el
 * EmailLog correlacionado cuando encuentra un DSN o una queja de spam. Ver
 * BounceProcessorService para el detalle de cómo se correlaciona.
 *
 * Reemplaza a documents:process-bounces (que solo cubría el buzón fijo de
 * Document) — ver migración de datos en el changelog de la fase 2.
 *
 * Sin ningún buzón habilitado, no hace nada (SUCCESS silencioso) para poder
 * dejarlo programado sin que falle cuando aún no se ha configurado ninguno.
 */
class ProcessEmailBouncesCommand extends Command
{
    /**
     * Fallos seguidos de UN buzón (conexión IMAP, no "0 mensajes
     * procesados") antes de avisar a manager/super-admin. No se avisa en el
     * primer fallo para no generar ruido por un blip transitorio de red.
     */
    private const CONSECUTIVE_FAILURES_THRESHOLD = 3;

    protected $signature = 'email-logs:process-bounces
                            {--limit=50 : Máximo de mensajes no leídos a procesar por buzón}';

    protected $description = 'Revisa los buzones de rebote IMAP configurados y marca como bounced/complained los EmailLog correlacionados';

    public function handle(BounceProcessorService $service, BounceMailboxesRepository $mailboxes): int
    {
        $enabled = $mailboxes->enabled();

        if ($enabled === []) {
            $this->line('Sin buzones de rebote habilitados. Nada que hacer.');

            return self::SUCCESS;
        }

        try {
            $result = $service->process((int) $this->option('limit'));
        } catch (Throwable $e) {
            Log::error('email-logs:process-bounces failed', ['error' => $e->getMessage()]);
            $this->error("Fallo al procesar los buzones de rebote: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->notifyBrokenMailboxes($mailboxes);

        $this->info(
            "Buzones: {$result['mailboxes']} — Procesados {$result['processed']} mensaje(s) — ".
            "{$result['matched']} correlacionado(s) con un envío, {$result['unmatched']} sin correlacionar."
        );

        return $result['connected'] || $result['mailboxes'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Notifica solo los buzones que ACABAN de cruzar el umbral en esta
     * corrida (consecutive_failures === umbral exacto) — no en cada corrida
     * siguiente mientras sigan rotos, para no generar spam. Vuelve a avisar
     * solo si primero se recuperan (BounceMailboxesRepository::recordHealth
     * resetea el contador a 0 en un éxito) y luego rompen de nuevo.
     */
    private function notifyBrokenMailboxes(BounceMailboxesRepository $mailboxes): void
    {
        $justBroken = array_filter(
            $mailboxes->all(),
            fn (array $m) => (int) ($m['consecutive_failures'] ?? 0) === self::CONSECUTIVE_FAILURES_THRESHOLD,
        );

        if ($justBroken === []) {
            return;
        }

        try {
            $admins = User::role(['manager', 'super-admin'])->get();
        } catch (Throwable $e) {
            Log::warning('email-logs:process-bounces: no se pudieron obtener administradores para notificar', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($admins->isEmpty()) {
            return;
        }

        foreach ($justBroken as $mailbox) {
            Notification::send($admins, new BounceProcessingFailedNotification(
                mailboxLabel: (string) ($mailbox['label'] ?? $mailbox['host'] ?? ($mailbox['id'] ?? 'buzón')),
                consecutiveFailures: (int) $mailbox['consecutive_failures'],
                lastError: (string) ($mailbox['last_error'] ?? 'desconocido'),
            ));
        }
    }
}
