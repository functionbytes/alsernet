<?php

namespace Modules\HelpdeskTickets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\SubmitFeedbackRequest;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Public feedback page for ticket recipients (the URL embedded in the
 * "rate your support experience" email). Owned by HelpdeskTickets — Helpdesk
 * does not import any ticket symbols anymore.
 */
class FeedbackController extends Controller
{
    public function show(string $ticketNumber): View
    {
        // Select only non-sensitive fields — customer email/phone/description stay hidden.
        // If the view needs more fields, add them here (never pass the full model).
        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->firstOrFail(['id', 'ticket_number', 'subject', 'rated_at', 'closed_at']);

        if ($ticket->rated_at) {
            return view('helpdesktickets::public.feedback-thanks', compact('ticket'));
        }

        return view('helpdesktickets::public.feedback-form', compact('ticket'));
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
            ->route('helpdesk.feedback.show', $ticket->ticket_number)
            ->with('success', 'Gracias por tu feedback');
    }
}
