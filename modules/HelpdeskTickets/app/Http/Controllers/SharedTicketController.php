<?php

namespace Modules\HelpdeskTickets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Vista pública de solo lectura del hilo de un ticket, protegida por firma
 * (URL::signedRoute, 30 días) — la alternativa segura a "Ver como el
 * cliente" que se descartó por suplantar sesión: aquí NO hay login, NO se
 * toca el guard/sesión del portal de cliente (CustomerPortalController),
 * solo se resuelve el ticket directamente si la firma es válida para ESE
 * ticket_number exacto. Nunca muestra notas internas ni datos sensibles del
 * cliente (email/teléfono) — mismo criterio que FeedbackController::show().
 */
class SharedTicketController extends Controller
{
    /**
     * Único punto de emisión del enlace — mismo patrón que
     * FeedbackController::signedShowUrl().
     */
    public static function signedShowUrl(Ticket $ticket): string
    {
        return URL::temporarySignedRoute(
            'helpdesk.shared-ticket.show',
            now()->addDays(30),
            ['ticketNumber' => $ticket->ticket_number]
        );
    }

    public function show(string $ticketNumber): View
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->with(['status', 'customer:id,name'])
            ->firstOrFail(['id', 'ticket_number', 'subject', 'status_id', 'customer_id', 'created_at', 'closed_at']);

        // sender_name es un accesor (TicketItem::getSenderNameAttribute())
        // que resuelve por author_id/user_id — hace falta seleccionarlos
        // (y cargar las relaciones) aunque no se devuelvan tal cual.
        $thread = $ticket->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->with(['author', 'user'])
            ->orderBy('created_at')
            ->get(['id', 'type', 'author_id', 'user_id', 'body', 'created_at'])
            ->map(fn ($item) => [
                'from_agent' => $item->isFromAgent(),
                // Nombre genérico para el agente (no se expone su nombre
                // real en un enlace que puede reenviarse) — el del cliente
                // sí es el suyo propio.
                'sender_name' => $item->isFromAgent() ? 'Soporte' : ($item->sender_name ?: 'Tú'),
                // body (texto plano), no el accesor content (que prefiere
                // html_body) — la vista escapa con {{ }} + pre-wrap, no
                // renderiza HTML.
                'body' => $item->body,
                'created_at_human' => $item->created_at?->diffForHumans(),
            ]);

        return view('helpdesktickets::public.shared-ticket', compact('ticket', 'thread'));
    }
}
