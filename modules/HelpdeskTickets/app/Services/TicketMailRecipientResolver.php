<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Gate;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketMail;

/**
 * Quién puede recibir un correo de un ticket.
 *
 * Son las reglas que impiden que la dirección corporativa se convierta en un
 * relé: a quién va el mensaje, a quién se puede poner en copia, y qué queda
 * registrado cuando se escribe a alguien que no es el cliente.
 *
 * Vivían como privados de TicketMailsController, entre catorce endpoints. Aquí
 * se leen juntas —que es como hay que revisarlas, porque se sostienen unas a
 * otras— y se pueden probar sin montar una petición HTTP.
 */
class TicketMailRecipientResolver
{
    /**
     * Destinatario real de un envío.
     *
     * Por defecto —campo vacío, o igual al cliente del ticket— siempre es el
     * cliente, NUNCA un valor arbitrario sin más. Pedir explícitamente otra
     * dirección exige el permiso `helpdesk.tickets.emails.send_to_any` y queda
     * anotado en el historial del ticket: escribir desde la dirección
     * corporativa a un tercero no puede hacerse sin dejar rastro.
     */
    public function resolveOutbound(?string $requested, Ticket $ticket): string
    {
        $customerEmail = $ticket->customer?->email;
        $requested = $requested !== null && $requested !== '' ? mb_strtolower(trim($requested)) : null;

        if ($requested === null) {
            abort_if(! $customerEmail, 422, 'El ticket no tiene un cliente con email al que enviar el correo.');

            return $customerEmail;
        }

        if ($customerEmail && $requested === mb_strtolower($customerEmail)) {
            return $customerEmail;
        }

        // Gate::authorize() y no $this->authorize(): fuera de un controlador no
        // existe el trait AuthorizesRequests, pero la comprobación es la misma
        // y sigue lanzando la excepción de autorización que Laravel convierte
        // en 403.
        Gate::authorize('sendToAnyRecipient', TicketMail::class);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'action_type' => 'mail_sent_to_arbitrary_recipient',
            'old_value' => $customerEmail,
            'new_value' => $requested,
            'metadata' => ['description' => 'Correo del ticket enviado a un destinatario distinto del cliente'],
        ]);

        return $requested;
    }

    /**
     * Direcciones «de confianza» para ir en Cc/Bcc de un correo de este
     * ticket: el propio cliente, cualquier dirección que ya haya aparecido en
     * el hilo (to/cc/bcc de correos previos) y los agentes que lo siguen — no
     * cualquier email válido.
     *
     * @return list<string>
     */
    public function participants(Ticket $ticket): array
    {
        $emails = collect();

        if ($ticket->customer?->email) {
            $emails->push(mb_strtolower($ticket->customer->email));
        }

        TicketMail::query()
            ->where('ticket_id', $ticket->id)
            ->get(['to', 'cc', 'bcc'])
            ->each(function (TicketMail $mail) use ($emails) {
                foreach (array_filter([$mail->to, $mail->cc, $mail->bcc]) as $field) {
                    foreach (explode(',', $field) as $address) {
                        $address = mb_strtolower(trim($address));

                        if ($address !== '') {
                            $emails->push($address);
                        }
                    }
                }
            });

        $ticket->watchers()->with('user:id,email')->get()->each(function ($watcher) use ($emails) {
            if ($watcher->user?->email) {
                $emails->push(mb_strtolower($watcher->user->email));
            }
        });

        return $emails->unique()->values()->all();
    }

    /**
     * Corta el envío si alguna dirección en copia no participa en el ticket.
     *
     * @param  list<string>  $addresses
     * @param  list<string>  $participants
     */
    public function assertParticipants(array $addresses, array $participants): void
    {
        $invalid = collect($addresses)
            ->reject(fn (string $address) => in_array(mb_strtolower(trim($address)), $participants, true))
            ->values();

        if ($invalid->isNotEmpty()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Estos destinatarios en copia no participan en el ticket: '.$invalid->implode(', '),
            ], 422));
        }
    }
}
