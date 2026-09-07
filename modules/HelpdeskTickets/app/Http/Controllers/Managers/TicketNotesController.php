<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketNoteRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Services\MentionService;

class TicketNotesController extends Controller
{
    public function __construct(
        private readonly MentionService $mentionService,
    ) {}

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

        // La nota también entra en el hilo, como la del composer.
        //
        // Escribir una nota desde la tarjeta del panel y desde el composer es
        // la misma acción para el agente, pero hasta ahora daban resultados
        // distintos: la del composer creaba un mensaje interno visible en la
        // conversación y la del panel solo una fila en "Notas del ticket", así
        // que la nota "desaparecía" del sitio donde se lee el ticket.
        $item = $ticket->items()->create([
            'type' => 'message',
            'user_id' => auth()->id(),
            'body' => $note->title ? $note->title."\n\n".$note->body : $note->body,
            'is_internal' => true,
            'metadata' => ['source' => 'ticket_note', 'note_id' => $note->id],
        ]);

        $note->forceFill(['ticket_item_id' => $item->id])->save();

        // Mismo servicio ya usado en mensajes del hilo
        // (TicketMessagingController::storeMessage) — antes solo
        // funcionaba ahí; una nota interna con @Nombre nunca notificaba.
        $this->mentionService->notifyMentions($note->body, $ticket);

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

        // Borrar la nota se lleva su mensaje del hilo: si no, quedaría en la
        // conversación una nota que ya no existe y que nadie puede retirar.
        if ($note->ticket_item_id) {
            TicketItem::where('id', $note->ticket_item_id)
                ->where('ticket_id', $ticket->id)
                ->delete();
        }

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
