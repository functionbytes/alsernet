<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketNoteRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketNote;

class TicketNotesController extends Controller
{
    /**
     * Store a newly created note in storage.
     */
    public function store(StoreTicketNoteRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('create', TicketNote::class);

        $note = TicketNote::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'color' => $request->input('color', 'yellow'),
            'is_pinned' => $request->boolean('is_pinned', false),
        ]);

        return response()->json($note, 201);
    }

    /**
     * Display the specified note.
     */
    public function show(Ticket $ticket, TicketNote $note): JsonResponse
    {
        $this->authorize('view', $ticket);

        if ($note->ticket_id !== $ticket->id) {
            abort(404);
        }

        return response()->json($note->load('user'));
    }

    /**
     * Remove the specified note from storage (soft delete).
     */
    public function destroy(Ticket $ticket, TicketNote $note): JsonResponse
    {
        if ($note->ticket_id !== $ticket->id) {
            abort(404);
        }

        $this->authorize('delete', $note);

        $note->delete();

        return response()->json(['message' => 'Nota eliminada exitosamente']);
    }

    /**
     * Toggle pin status for a note.
     */
    public function pin(Ticket $ticket, TicketNote $note): JsonResponse
    {
        if ($note->ticket_id !== $ticket->id) {
            abort(404);
        }

        $this->authorize('update', $note);

        $note->togglePin();

        return response()->json([
            'message' => $note->is_pinned ? 'Nota fijada' : 'Nota desfijada',
            'is_pinned' => $note->is_pinned,
        ]);
    }

    /**
     * Change the color of a note.
     */
    public function changeColor(Ticket $ticket, TicketNote $note): JsonResponse
    {
        if ($note->ticket_id !== $ticket->id) {
            abort(404);
        }

        $this->authorize('update', $note);

        $request = request();
        $color = $request->input('color');

        if (! in_array($color, ['yellow', 'blue', 'green', 'red', 'purple', 'orange'])) {
            return response()->json(['error' => 'Color inválido'], 422);
        }

        $note->changeColor($color);

        return response()->json([
            'message' => 'Color de nota actualizado',
            'color' => $note->color,
        ]);
    }
}
