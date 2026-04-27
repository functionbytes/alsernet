<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\SubmitFeedbackRequest;
use Modules\HelpdeskTickets\Models\Ticket;

class FeedbackController extends Controller
{
    public function show(string $ticketNumber): View
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        if ($ticket->rated_at) {
            return view('helpdesk::public.feedback-thanks', compact('ticket'));
        }

        return view('helpdesk::public.feedback-form', compact('ticket'));
    }

    public function submit(SubmitFeedbackRequest $request, string $ticketNumber)
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
