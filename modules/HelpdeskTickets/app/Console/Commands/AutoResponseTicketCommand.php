<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\CannedReply;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Services\TicketSettings;

class AutoResponseTicketCommand extends Command
{
    protected $signature = 'ticket:autoresponseticket';

    protected $description = 'Send automatic responses to new tickets that have not received a response';

    public function handle(): int
    {
        try {
            $settings = app(TicketSettings::class);

            if (! $settings->boolean('auto_responsetime_ticket', false)) {
                $this->info('Automatic first-response messages are disabled in Helpdesk settings.');

                return Command::SUCCESS;
            }

            $hours = $settings->integer('auto_responsetime_ticket_time', 48);

            $tickets = Ticket::query()
                ->whereNull('first_response_at')
                // La marca de "ya contestado automáticamente" es el propio
                // mensaje de sistema, no first_response_at (ver abajo).
                ->whereDoesntHave('items', fn ($q) => $q->whereNull('user_id')
                    ->whereNull('author_id')
                    ->where('metadata', 'like', '%auto_response%'))
                ->where('created_at', '<', now()->subHours($hours))
                ->whereNull('closed_at')
                ->cursor();

            $autoReply = CannedReply::where('category', 'auto-response')
                ->orWhere('tags', 'like', '%auto-response%')
                ->first();

            $defaultBody = 'Hemos recibido su solicitud. Un agente se pondrá en contacto con usted a la brevedad posible.';
            $body = $autoReply?->body ?? $defaultBody;

            $count = 0;

            foreach ($tickets as $ticket) {
                try {
                    $item = TicketItem::create([
                        'ticket_id' => $ticket->id,
                        'type' => 'message',
                        'body' => $body,
                        'is_internal' => false,
                        // Sin user_id ni author_id = mensaje de sistema (la
                        // columna sender_type que se mandaba aquí no existe).
                        'metadata' => ['auto_response' => true],
                    ]);

                    // Sin first_response_at: un acuse automático no es una
                    // respuesta de un agente. Marcarlo escondía el
                    // incumplimiento del SLA de primera respuesta y rebajaba
                    // el tiempo medio de primera respuesta de los informes.
                    $ticket->update([
                        'last_message_at' => now(),
                    ]);
                    // Persisting the system message alone did not send an
                    // email to the customer. Reuse the same event pipeline as
                    // a real agent reply so the automatic response is visible
                    // in the portal and delivered through the configured
                    // ticket mailbox.
                    MessageAdded::dispatch($item);
                    $count++;
                } catch (\Throwable $e) {
                    Log::error('AutoResponse failed for ticket', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Sent auto-response to {$count} ticket(s).");
            Log::info('AutoResponseTicket: auto-responses sent.', ['count' => $count, 'hours' => $hours]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('AutoResponseTicket command failed', ['error' => $e->getMessage()]);
            $this->error('Command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
