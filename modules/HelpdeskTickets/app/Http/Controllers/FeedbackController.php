<?php

namespace Modules\HelpdeskTickets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\SubmitFeedbackRequest;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Public feedback page for ticket recipients (the URL embedded in the
 * "rate your support experience" email). Owned by HelpdeskTickets — Helpdesk
 * does not import any ticket symbols anymore.
 *
 * Ambas rutas van detrás del middleware `signed`: el ticket_number es
 * secuencial y sin firma cualquiera podía leer el subject y puntuar tickets
 * ajenos (IDOR). Los enlaces se emiten con URL::signedRoute() — ver
 * self::signedShowUrl() y UpdateTicketOnClose.
 */
class FeedbackController extends Controller
{
    /**
     * URL firmada de la página pública de feedback de un ticket. Único punto
     * de emisión del enlace (emails, listeners...).
     */
    public static function signedShowUrl(Ticket $ticket): string
    {
        return URL::signedRoute('helpdesk.feedback.show', ['ticketNumber' => $ticket->ticket_number]);
    }

    public function show(string $ticketNumber): View
    {
        // Select only non-sensitive fields — customer email/phone/description stay hidden.
        // If the view needs more fields, add them here (never pass the full model).
        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->firstOrFail(['id', 'ticket_number', 'subject', 'rated_at', 'closed_at']);

        if ($ticket->rated_at) {
            return view('helpdesktickets::public.feedback-thanks', compact('ticket'));
        }

        // El formulario postea a una URL firmada: la ruta submit también exige
        // firma y la firma solo cubre la query string, no el cuerpo del POST.
        $submitUrl = URL::signedRoute('helpdesk.feedback.submit', ['ticketNumber' => $ticket->ticket_number]);

        return view('helpdesktickets::public.feedback-form', compact('ticket', 'submitUrl'));
    }

    public function submit(SubmitFeedbackRequest $request, string $ticketNumber): RedirectResponse
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        $validated = $request->validated();

        if (! $ticket->rated_at) {
            $ticket->update([
                'rating' => $validated['rating'],
                'rating_comment' => $validated['comment'] ?? null,
                'rated_at' => now(),
            ]);
        }

        return redirect()
            ->to(self::signedShowUrl($ticket))
            ->with('success', 'Gracias por tu feedback');
    }
}
