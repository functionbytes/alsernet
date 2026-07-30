<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Events\TicketResolved;
use Modules\HelpdeskTickets\Http\Requests\Managers\LinkTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\MergeTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\SnoozeTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketWatcher;

class TicketLifecycleController extends Controller
{
    public function close(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('close', $ticket);

        // Dependencias: no cerrar si un ticket bloqueante sigue abierto, salvo
        // que el manager fuerce explícitamente (force=1).
        if (! $request->boolean('force')) {
            $blockers = $ticket->openBlockers();

            if ($blockers->isNotEmpty()) {
                $numbers = $blockers->pluck('ticket_number')->implode(', ');
                $message = "No se puede cerrar: hay tickets bloqueantes abiertos ({$numbers}).";

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'blockers' => $blockers->map(fn ($t) => [
                            'id' => $t->id,
                            'ticket_number' => $t->ticket_number,
                            'subject' => $t->subject,
                        ])->all(),
                    ], 409);
                }

                return back()->with('error', $message);
            }
        }

        $ticket->close();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.ticket_closed'),
                'ticket' => $ticket->fresh(),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_closed'));
    }

    public function resolve(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('resolve', $ticket);

        $ticket->resolve();

        TicketResolved::dispatch($ticket);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.ticket_resolved'),
                'ticket' => $ticket->fresh(),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_resolved'));
    }

    public function reopen(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('reopen', $ticket);

        $ticket->reopen();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.ticket_reopened'),
                'ticket' => $ticket->fresh(),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_reopened'));
    }

    public function archive(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('archive', $ticket);

        $ticket->archive();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.ticket_archived'),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_archived'));
    }

    public function unarchive(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $ticket->update(['archived_at' => null]);

        return back()->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_unarchived'));
    }

    public function merge(MergeTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validated();

        $targetTicket = Ticket::findOrFail($validated['merge_into_id']);

        $this->authorize('merge', $ticket);
        $this->authorize('update', $targetTicket);

        DB::transaction(function () use ($ticket, $targetTicket) {
            $ticket->items()->update(['ticket_id' => $targetTicket->id]);

            // Migrar el resto de datos asociados para no perderlos al borrar el
            // ticket origen: historial, emails, notas, comentarios y tiempos.
            // history() se actualiza a nivel de query (los modelos TicketHistory
            // son inmutables a nivel de instancia, pero aquí solo se reapunta
            // la FK, no se reescribe el registro).
            $ticket->history()->update(['ticket_id' => $targetTicket->id]);
            $ticket->mails()->update(['ticket_id' => $targetTicket->id]);
            $ticket->timeEntries()->update(['ticket_id' => $targetTicket->id]);
            TicketNote::withTrashed()->where('ticket_id', $ticket->id)->update(['ticket_id' => $targetTicket->id]);
            TicketComment::withTrashed()->where('ticket_id', $ticket->id)->update(['ticket_id' => $targetTicket->id]);

            $ticket->watchers()->each(function (TicketWatcher $watcher) use ($targetTicket) {
                TicketWatcher::firstOrCreate([
                    'ticket_id' => $targetTicket->id,
                    'user_id' => $watcher->user_id,
                ]);
            });

            // Reapuntar enlaces del origen al destino, descartando los que
            // quedarían auto-enlazados o duplicados en el destino.
            $ticket->links()->get()->each(function (TicketLink $link) use ($targetTicket) {
                $duplicate = $link->linked_ticket_id === $targetTicket->id
                    || TicketLink::where('ticket_id', $targetTicket->id)
                        ->where('linked_ticket_id', $link->linked_ticket_id)
                        ->exists();

                $duplicate ? $link->delete() : $link->update(['ticket_id' => $targetTicket->id]);
            });

            $ticket->linkedBy()->get()->each(function (TicketLink $link) use ($targetTicket) {
                $duplicate = $link->ticket_id === $targetTicket->id
                    || TicketLink::where('ticket_id', $link->ticket_id)
                        ->where('linked_ticket_id', $targetTicket->id)
                        ->exists();

                $duplicate ? $link->delete() : $link->update(['linked_ticket_id' => $targetTicket->id]);
            });

            $targetTicket->items()->create([
                'type' => 'system',
                'body' => "Merged from #{$ticket->ticket_number}",
                'metadata' => ['merged_from_ticket_id' => $ticket->id],
            ]);

            $ticket->close();
            $ticket->delete();
        });

        return redirect()->route('manager.helpdesk.tickets.show', $targetTicket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_merged', ['source' => $ticket->ticket_number, 'target' => $targetTicket->ticket_number]));
    }

    public function watch(Ticket $ticket): JsonResponse
    {
        $this->authorize('watch', $ticket);

        TicketWatcher::addWatcher($ticket->id, auth()->id());

        return response()->json(['watching' => true, 'message' => __('helpdesktickets::helpdesktickets.messages.ticket_watched')]);
    }

    public function unwatch(Ticket $ticket): JsonResponse
    {
        $this->authorize('watch', $ticket);

        TicketWatcher::removeWatcher($ticket->id, auth()->id());

        return response()->json(['watching' => false, 'message' => __('helpdesktickets::helpdesktickets.messages.ticket_unwatched')]);
    }

    public function snooze(SnoozeTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $until = $request->validated()['snoozed_until'];
        $ticket->update(['snoozed_until' => $until, 'snoozed_by' => auth()->id()]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket pospuesto.',
            'data' => ['snoozed_until' => $ticket->snoozed_until?->toIso8601String()],
        ]);
    }

    public function unsnooze(Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $ticket->update(['snoozed_until' => null, 'snoozed_by' => null]);

        return response()->json(['success' => true, 'message' => 'Ticket reactivado.']);
    }

    public function linkTicket(LinkTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validated();

        if ($validated['linked_ticket_id'] == $ticket->id) {
            return back()->withErrors(['linked_ticket_id' => 'No puedes enlazar un ticket consigo mismo.']);
        }

        // El usuario también debe poder ver el ticket destino que va a enlazar.
        $linkedTicket = Ticket::findOrFail($validated['linked_ticket_id']);
        $this->authorize('view', $linkedTicket);

        TicketLink::firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'linked_ticket_id' => $validated['linked_ticket_id'],
            ],
            [
                'link_type' => $validated['link_type'] ?? 'related',
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Ticket enlazado correctamente.');
    }

    public function unlinkTicket(Ticket $ticket, int $linkId): RedirectResponse
    {
        $this->authorize('update', $ticket);

        TicketLink::where('ticket_id', $ticket->id)
            ->where('id', $linkId)
            ->delete();

        return back()->with('success', 'Enlace eliminado.');
    }
}
