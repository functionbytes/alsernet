<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
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

            return self::FAILURE;
        }

        if (! $result['connected']) {
            $this->warn('No se pudo conectar (host de IMAP no configurado o webklex/php-imap ausente).');

            return self::FAILURE;
        }

        $this->info("Procesados {$result['processed']} mensaje(s) — {$result['matched']} correlacionado(s) con un envío, {$result['unmatched']} sin correlacionar.");

        return self::SUCCESS;
    }
}
