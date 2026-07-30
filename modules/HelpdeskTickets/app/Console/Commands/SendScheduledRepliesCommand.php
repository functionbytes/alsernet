<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\TicketScheduledReply;

/**
 * Envía las respuestas programadas (send later) cuya hora ya venció: crea el
 * TicketItem con el agente que la redactó y dispara MessageAdded, que notifica
 * al cliente por el mismo camino que una respuesta manual. Barrido idempotente
 * (filtra sent_at null), consistente con el resto de comandos de tickets.
 */
class SendScheduledRepliesCommand extends Command
{
    protected $signature = 'ticket:send-scheduled-replies';

    protected $description = 'Deliver scheduled ticket replies whose send time has passed';

    public function handle(): int
    {
        $sent = 0;

        TicketScheduledReply::query()
            ->whereNull('sent_at')
            ->where('deliver_at', '<=', now())
            ->with('ticket')
            ->chunkById(200, function ($replies) use (&$sent) {
                foreach ($replies as $reply) {
                    $sent += $this->deliver($reply) ? 1 : 0;
                }
            });

        if ($sent > 0) {
            Log::info('SendScheduledReplies: respuestas programadas enviadas', ['sent' => $sent]);
        }

        $this->info("Enviadas {$sent} respuesta(s) programada(s).");

        return self::SUCCESS;
    }

    private function deliver(TicketScheduledReply $reply): bool
    {
        // El ticket pudo eliminarse tras programar la respuesta: la marcamos
        // como procesada para no reintentarla indefinidamente.
        if (! $reply->ticket) {
            $reply->update(['sent_at' => now()]);

            return false;
        }

        $item = $reply->ticket->items()->create([
            'type' => 'message',
            'user_id' => $reply->user_id,
            'body' => $reply->body,
            'is_internal' => $reply->is_internal,
        ]);

        $data = ['last_message_at' => now()];
        if (! $reply->ticket->first_response_at && ! $reply->is_internal) {
            $data['first_response_at'] = now();
        }
        $reply->ticket->update($data);

        MessageAdded::dispatch($item);

        $reply->update(['sent_at' => now()]);

        return true;
    }
}
