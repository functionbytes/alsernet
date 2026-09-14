<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Document\Entities\Document;
use Modules\Document\Services\DocumentEmailService;

/**
 * Rota los `uid` DÉBILES de documentos (formato antiguo tipo `uniqid()`, 13 hex,
 * basado en timestamp → enumerable) a UUID aleatorio. Cierra el IDOR de las
 * rutas públicas sin auth `/api/documents/{uid}/validation|files` que exponen y
 * mutan PII sensible (DNI/licencias) usando SOLO el uid como control de acceso.
 *
 * Los documentos creados por el código actual ya usan `Str::uuid()` (trait HasUid),
 * así que solo se rotan los legacy/migrados. Al cambiar el uid, cualquier enlace
 * de portal con el uid antiguo deja de funcionar: con `--notify` se reenvía el
 * enlace (nuevo uid) a los documentos aún ACTIVOS (no terminales).
 */
class RotateWeakDocumentUids extends Command
{
    protected $signature = 'documents:rotate-weak-uids
        {--dry-run : Solo informa qué haría, sin cambiar nada}
        {--notify : Reenvía el enlace del portal a los documentos no terminales rotados}
        {--limit= : Limita el número de documentos a procesar}
        {--force : Omite la confirmación}';

    protected $description = 'Rota uids débiles (no-UUID) de documentos a UUID para cerrar el IDOR de las rutas públicas; opcionalmente reenvía el enlace del portal a los activos.';

    private const UUID_REGEX = '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$';

    private const TERMINAL_STATUS_KEYS = ['approved', 'cancelled', 'completed'];

    public function handle(DocumentEmailService $emailService): int
    {
        $terminalStatusIds = DB::table('document_statuses')
            ->whereIn('key', self::TERMINAL_STATUS_KEYS)
            ->pluck('id')
            ->all();

        $query = Document::query()
            ->whereRaw('uid NOT REGEXP ?', [self::UUID_REGEX])
            ->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $weak = $query->get(['id', 'uid', 'status_id']);
        $total = $weak->count();

        if ($total === 0) {
            $this->info('✅ No hay uids débiles: todos son UUID. Nada que rotar.');

            return self::SUCCESS;
        }

        $nonTerminal = $weak->reject(fn ($d) => in_array($d->status_id, $terminalStatusIds, true))->count();

        $this->info("uids débiles a rotar: {$total}");
        $this->line("  · activos/no-terminales (candidatos a reenvío): {$nonTerminal}");
        $this->line('  · terminales (aprobados/cancelados/completados): '.($total - $nonTerminal));

        if ($this->option('dry-run')) {
            $this->comment('DRY-RUN: no se ha cambiado nada.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm("Rotar {$total} uids a UUID? Los enlaces de portal con el uid antiguo dejarán de funcionar.")) {
            return self::SUCCESS;
        }

        $notify = (bool) $this->option('notify');
        $rotated = 0;
        $notified = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($weak as $doc) {
            // Update quirúrgico: sin observers/hooks del modelo (evita side effects
            // del updating() como recálculos de estado o envíos no deseados).
            DB::table('documents')
                ->where('id', $doc->id)
                ->update(['uid' => (string) Str::uuid(), 'updated_at' => now()]);
            $rotated++;

            if ($notify && ! in_array($doc->status_id, $terminalStatusIds, true)) {
                $fresh = Document::find($doc->id); // recoge el uid nuevo

                try {
                    if ($fresh && $emailService->sendReminder($fresh, null)) {
                        $notified++;
                    }
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->warn("Reenvío falló para documento {$doc->id}: {$e->getMessage()}");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Rotados {$rotated} uids a UUID.");

        if ($notify) {
            $this->info("📧 Enlaces de portal reenviados (no terminales): {$notified}.");
        } else {
            $this->comment("💡 Los enlaces con uid antiguo ya no funcionan. Usa --notify para reenviar el enlace a los {$nonTerminal} documentos activos.");
        }

        return self::SUCCESS;
    }
}
