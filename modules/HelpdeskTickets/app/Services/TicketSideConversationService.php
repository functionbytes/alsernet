<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Mail\TicketSideConversationMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSideConversation;
use Modules\HelpdeskTickets\Models\TicketSideConversationMessage;
use Modules\HelpdeskTickets\Notifications\TicketSideConversationMessageNotification;

/**
 * Hilos laterales privados de un ticket (side conversations): colaborar con un
 * compañero o un contacto externo sin que el cliente lo vea. El compañero recibe
 * una notificación in-app; al externo se le envía un email. La respuesta entrante
 * del externo queda para una fase posterior.
 */
class TicketSideConversationService
{
    /**
     * Crea un side conversation con su primer mensaje y avisa al participante.
     *
     * @param  array{subject: string, participant_type: string, participant_email?: ?string, participant_user_id?: ?int, body: string}  $data
     */
    public function create(Ticket $ticket, array $data, User $creator): TicketSideConversation
    {
        // Nota: transacción en la conexión por defecto (patrón del merge de
        // tickets). Abrirla en la conexión 'helpdesk' choca con la que el trait
        // SharesHelpdeskPdo mantiene abierta durante los tests.
        return DB::transaction(function () use ($ticket, $data, $creator) {
            $side = TicketSideConversation::create([
                'ticket_id' => $ticket->id,
                'subject' => $data['subject'],
                'participant_type' => $data['participant_type'],
                'participant_email' => $data['participant_email'] ?? null,
                'participant_user_id' => $data['participant_user_id'] ?? null,
                'status' => 'open',
                'created_by' => $creator->id,
            ]);

            $message = $this->appendMessage($side, $data['body'], $creator);

            // Rastro en el timeline del ticket (interno, no visible al cliente).
            $ticket->items()->create([
                'type' => 'system',
                'user_id' => $creator->id,
                'body' => "Conversación lateral iniciada: {$side->subject}",
                'is_internal' => true,
                'metadata' => ['side_conversation_id' => $side->id],
            ]);

            $this->notifyParticipant($side, $message);

            return $side;
        });
    }

    /**
     * Añade un mensaje saliente al hilo y reavisa al participante.
     */
    public function addMessage(TicketSideConversation $side, string $body, User $author): TicketSideConversationMessage
    {
        $message = $this->appendMessage($side, $body, $author);

        $this->notifyParticipant($side, $message);

        return $message;
    }

    public function close(TicketSideConversation $side): void
    {
        $side->update(['status' => 'closed']);
    }

    public function reopen(TicketSideConversation $side): void
    {
        $side->update(['status' => 'open']);
    }

    private function appendMessage(TicketSideConversation $side, string $body, User $author): TicketSideConversationMessage
    {
        return $side->messages()->create([
            'user_id' => $author->id,
            'direction' => 'outbound',
            'body' => $body,
        ]);
    }

    private function notifyParticipant(TicketSideConversation $side, TicketSideConversationMessage $message): void
    {
        if ($side->participant_type === 'team' && $side->participant_user_id) {
            $participant = User::find($side->participant_user_id);
            $participant?->notify(new TicketSideConversationMessageNotification($side, $message));

            return;
        }

        if ($side->participant_type === 'external_email' && $side->participant_email) {
            Mail::to($side->participant_email)->queue(new TicketSideConversationMail($side, $message->body));
        }
    }
}
