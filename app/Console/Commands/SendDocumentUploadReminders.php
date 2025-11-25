<?php

namespace App\Console\Commands;

use App\Events\Documents\DocumentReminderRequested;
use App\Models\Order\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDocumentUploadReminders extends Command
{
    protected $signature = 'documents:send-reminders {--force : Reenviar aunque ya se haya enviado el recordatorio}';

    protected $description = 'Detecta pedidos pagados en Prestashop y envía recordatorios de documentación pendientes.';

    public function handle(): int
    {
        $paidStatuses = collect(config('documents.paid_statuses', []))
            ->filter(fn ($state) => is_numeric($state) && (int) $state > 0)
            ->map(fn ($state) => (int) $state)
            ->unique()
            ->values();

        if ($paidStatuses->isEmpty()) {
            $this->warn('No hay estados pagados configurados (DOCUMENTS_PRESTASHOP_PAID_STATUS_IDS).');
            return Command::SUCCESS;
        }

        $query = Document::query()
            ->with(['order', 'customer'])
            ->whereNotNull('order_id')
            ->whereNull('upload_at')
            ->whereHas('order', function ($builder) use ($paidStatuses) {
                $builder->whereIn('current_state', $paidStatuses);
            });

        if (!$this->option('force')) {
            $query->whereNull('reminder_sent_at');
        }

        $processed = 0;

        $query->chunkById(100, function ($documents) use (&$processed) {
            /** @var Document $document */
            foreach ($documents as $document) {
                event(new DocumentReminderRequested($document));

                $document->forceFill([
                    'reminder_sent_at' => now(),
                ])->save();

                $processed++;
            }
        });

        if ($processed > 0) {
            Log::info('Document upload reminders dispatched', ['count' => $processed]);
        }

        $this->info("Recordatorios encolados: {$processed}");

        return Command::SUCCESS;
    }
}
