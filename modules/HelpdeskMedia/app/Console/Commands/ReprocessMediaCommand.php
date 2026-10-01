<?php

namespace Modules\HelpdeskMedia\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskMedia\Jobs\ProcessConversationMediaJob;
use Modules\HelpdeskMedia\Jobs\ProcessTicketAttachmentJob;
use Modules\HelpdeskMedia\Services\ConversationMediaProcessor;
use Modules\HelpdeskMedia\Services\TicketAttachmentProcessor;
use Modules\HelpdeskTickets\Models\TicketAttachment;

class ReprocessMediaCommand extends Command
{
    protected $signature = 'media:reprocess
        {--conversation= : ID de conversación}
        {--ticket= : ID de ticket}
        {--since= : Fecha (2026-09-01) o relativa (7d, 12h)}
        {--force : Reprocesa también lo ya procesado}
        {--sync : Ejecuta en el momento en vez de encolar}';

    protected $description = 'Procesa adjuntos antiguos de conversaciones y tickets (antivirus, imágenes, audio)';

    public function handle(): int
    {
        $conversation = $this->option('conversation');
        $ticket = $this->option('ticket');
        $since = $this->parseSince();

        if ($conversation === null && $ticket === null && $since === null) {
            $this->error('Indica --conversation, --ticket o --since.');

            return self::FAILURE;
        }

        $both = $conversation === null && $ticket === null;
        $total = 0;

        if ($conversation !== null || $both) {
            $total += $this->conversations($conversation, $since);
        }

        if ($ticket !== null || $both) {
            $total += $this->tickets($ticket, $since);
        }

        $this->info("{$total} adjuntos/mensajes ".($this->option('sync') ? 'procesados' : 'encolados').'.');

        return self::SUCCESS;
    }

    private function conversations(?string $conversationId, ?Carbon $since): int
    {
        $count = 0;

        ConversationItem::query()
            ->whereNotNull('attachment_urls')
            ->when($conversationId !== null, fn ($q) => $q->where('conversation_id', (int) $conversationId))
            ->when($since !== null, fn ($q) => $q->where('created_at', '>=', $since))
            ->chunkById(200, function ($items) use (&$count): void {
                foreach ($items as $item) {
                    $hasAttachments = $item->getAttributes()['attachment_urls'] !== '[]';

                    if (! $hasAttachments || (! $this->option('force') && ! ConversationMediaProcessor::hasPending($item))) {
                        continue;
                    }

                    $this->option('sync')
                        ? ProcessConversationMediaJob::dispatchSync($item->id, (bool) $this->option('force'))
                        : ProcessConversationMediaJob::dispatch($item->id, (bool) $this->option('force'));
                    $count++;
                }
            });

        return $count;
    }

    private function tickets(?string $ticketId, ?Carbon $since): int
    {
        if (! TicketAttachmentProcessor::columnsReady()) {
            $this->warn('Faltan las columnas de media en helpdesk_ticket_attachments: ejecuta las migraciones.');

            return 0;
        }

        $count = 0;

        TicketAttachment::query()
            ->when($ticketId !== null, fn ($q) => $q->whereHas('message', fn ($m) => $m->where('ticket_id', (int) $ticketId)))
            ->when($since !== null, fn ($q) => $q->where('created_at', '>=', $since))
            ->when(! $this->option('force'), fn ($q) => $q->whereNull('processed_at'))
            ->chunkById(200, function ($attachments) use (&$count): void {
                foreach ($attachments as $attachment) {
                    $this->option('sync')
                        ? ProcessTicketAttachmentJob::dispatchSync($attachment->id, (bool) $this->option('force'))
                        : ProcessTicketAttachmentJob::dispatch($attachment->id, (bool) $this->option('force'));
                    $count++;
                }
            });

        return $count;
    }

    private function parseSince(): ?Carbon
    {
        $value = $this->option('since');

        if ($value === null) {
            return null;
        }

        if (preg_match('/^(\d+)([dh])$/', (string) $value, $matches)) {
            return $matches[2] === 'd' ? now()->subDays((int) $matches[1]) : now()->subHours((int) $matches[1]);
        }

        return Carbon::parse((string) $value);
    }
}
