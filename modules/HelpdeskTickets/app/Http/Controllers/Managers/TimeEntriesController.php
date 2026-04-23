<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;

class TimeEntriesController extends Controller
{
    /**
     * List time entries for a ticket, newest first.
     */
    public function index(Ticket $ticket): JsonResponse
    {
        $entries = $ticket->timeEntries()
            ->with('user:id,name')
            ->orderBy('logged_at', 'desc')
            ->limit(200)
            ->get()
            ->map(fn ($entry) => array_merge($entry->toArray(), [
                'formatted_duration' => $entry->formatted_duration,
            ]));

        return response()->json([
            'entries' => $entries,
            'total_minutes' => $ticket->total_time_minutes,
            'formatted_total' => $ticket->formatted_total_time,
        ]);
    }

    /**
     * Log a new time entry for a ticket.
     */
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'logged_at' => ['nullable', 'date'],
        ]);

        $entry = TicketTimeEntry::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'description' => $validated['description'] ?? null,
            'minutes' => $validated['minutes'],
            'logged_at' => $validated['logged_at'] ?? now(),
        ]);

        $entry->load('user:id,name');

        return response()->json(
            array_merge($entry->toArray(), ['formatted_duration' => $entry->formatted_duration]),
            201
        );
    }

    /**
     * Delete a time entry. Only the owner or a super-admin may do this.
     */
    public function destroy(Ticket $ticket, TicketTimeEntry $timeEntry): JsonResponse
    {
        $this->authorize('delete', $timeEntry);

        $timeEntry->delete();

        return response()->json(null, 204);
    }
}
