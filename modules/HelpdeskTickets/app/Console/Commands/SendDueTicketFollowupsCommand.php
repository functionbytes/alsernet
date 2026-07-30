<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\TicketFollowup;
use Modules\HelpdeskTickets\Notifications\TicketFollowupDueNotification;

/**
 * Envía los recordatorios de seguimiento cuya fecha ya venció: notifica al
 * usuario que lo programó y marca el followup como enviado. Barrido idempotente
 * (filtra is_sent=false), consistente con el resto de comandos de tickets.
 */
class SendDueTicketFollowupsCommand extends Command
{
    protected $signature = 'ticket:send-followups';

    protected $description = 'Notify scheduled ticket follow-ups whose due time has passed';

    public function handle(): int
    {
        $sent = 0;

        TicketFollowup::query()
            ->where('is_sent', false)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with(['ticket', 'user'])
            ->chunkById(200, function ($followups) use (&$sent) {
                foreach ($followups as $followup) {
                    if ($followup->user) {
                        $followup->user->notify(new TicketFollowupDueNotification($followup));
                    }

                    $followup->update(['is_sent' => true, 'sent_at' => now()]);
                    $sent++;
                }
            });

        if ($sent > 0) {
            Log::info('SendDueTicketFollowups: recordatorios enviados', ['sent' => $sent]);
        }

        $this->info("Enviados {$sent} recordatorio(s) de seguimiento.");

        return self::SUCCESS;
    }
}
