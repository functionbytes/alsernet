<?php

namespace Modules\Document\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\Setting;
use Modules\Document\Notifications\BounceProcessingFailedNotification;
use Modules\Document\Services\DocumentBounceProcessorService;

/**
 * Revisa la bandeja de rebotes (documents.bounce_imap_*) y actualiza el EmailLog
 * correlacionado a 'bounced' cuando encuentra un DSN. Ver DocumentBounceProcessorService
 * para el detalle de cómo se correlaciona (por Message-ID, sin heurística de fallback).
 *
 * Gateado por documents.bounce_imap_enabled: sin activar, no hace nada (SUCCESS
 * silencioso) para poder dejarlo programado sin que falle cuando no está configurado.
 */
class ProcessEmailBouncesCommand extends Command
{
    /**
     * Fallos seguidos (conexión IMAP, no "0 mensajes procesados") antes de avisar
     * a manager/super-admin. No se avisa en el primer fallo para no generar ruido
     * por un blip transitorio de red — solo cuando ya lleva un rato roto de verdad.
     */
    private const CONSECUTIVE_FAILURES_THRESHOLD = 3;

    protected $signature = 'documents:process-bounces
                            {--limit=50 : Máximo de mensajes no leídos a procesar}';

    protected $description = 'Revisa la bandeja de rebotes IMAP y marca como bounced los EmailLog correlacionados';

    public function handle(DocumentBounceProcessorService $service): int
    {
        if (Setting::get('documents.bounce_imap_enabled', 'no') !== 'yes') {
            $this->line('Bounce IMAP desactivado (documents.bounce_imap_enabled != yes). Nada que hacer.');

            return self::SUCCESS;
        }

        try {
            $result = $service->process((int) $this->option('limit'));
        } catch (\Throwable $e) {
            Log::error('documents:process-bounces failed', ['error' => $e->getMessage()]);
            $this->error("Fallo al conectar/procesar la bandeja de rebotes: {$e->getMessage()}");
            $this->registerFailure($e->getMessage());

            return self::FAILURE;
        }

        if (! $result['connected']) {
            $this->warn('No se pudo conectar (host de IMAP no configurado o webklex/php-imap ausente).');
            $this->registerFailure('No se pudo conectar (host no configurado o webklex/php-imap ausente).');

            return self::FAILURE;
        }

        $this->registerSuccess();
        $this->info("Procesados {$result['processed']} mensaje(s) — {$result['matched']} correlacionado(s) con un envío, {$result['unmatched']} sin correlacionar.");

        return self::SUCCESS;
    }

    /**
     * Incrementa el contador de fallos seguidos (Settings, sobrevive entre
     * ejecuciones del comando) y notifica a admins al cruzar el umbral.
     */
    private function registerFailure(string $error): void
    {
        $count = ((int) Setting::get('documents.bounce_imap_consecutive_failures', '0')) + 1;
        Setting::set('documents.bounce_imap_consecutive_failures', (string) $count);

        if ($count < self::CONSECUTIVE_FAILURES_THRESHOLD) {
            return;
        }

        // Solo notificar UNA vez al cruzar el umbral, no en cada ejecución
        // siguiente mientras siga roto (evita spam) — se vuelve a notificar
        // solo si primero se recupera (registerSuccess resetea el contador).
        if ($count > self::CONSECUTIVE_FAILURES_THRESHOLD) {
            return;
        }

        $this->notifyAdmins($count, $error);
    }

    private function registerSuccess(): void
    {
        Setting::set('documents.bounce_imap_consecutive_failures', '0');
    }

    private function notifyAdmins(int $consecutiveFailures, string $error): void
    {
        try {
            $admins = User::role(['manager', 'super-admin'])->get();
        } catch (\Throwable $e) {
            Log::warning('documents:process-bounces: no se pudieron obtener administradores para notificar', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new BounceProcessingFailedNotification($consecutiveFailures, $error));
    }
}
