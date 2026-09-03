<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Events\TicketResolved;
use Modules\HelpdeskTickets\Http\Requests\Managers\LinkTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\MergeTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\SnoozeTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketWatcher;
use Modules\HelpdeskTickets\Services\SlaService;

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

        // reason: key de close_reasons, o el texto libre del campo "Otro
        // motivo" del modal — se recorta a 100 (columna string(100)) para no
        // reventar en modo estricto si alguien pega texto largo.
        // La causa raíz se valida contra el catálogo: es un campo de informe,
        // no texto libre, y una clave inventada lo estropearía en silencio.
        $rootCause = $request->input('root_cause');
        if ($rootCause !== null && ! array_key_exists($rootCause, config('helpdesktickets.close_root_causes', []))) {
            $rootCause = null;
        }

        $ticket->close(Str::limit((string) $request->input('reason'), 100, '') ?: null, [
            'root_cause' => $rootCause,
            'summary' => Str::limit((string) $request->input('summary'), 2000, '') ?: null,
            // Cerrar disparaba SIEMPRE la encuesta de satisfacción; en un
            // cierre por spam o duplicado preguntar sobra.
            'skip_survey' => $request->boolean('skip_survey'),
        ]);

        // Bug real (ago-2026): este endpoint es la vía real del botón "Cerrar
        // ticket" de la UI y nunca disparaba TicketClosed, así que la encuesta
        // CSAT automática (UpdateTicketOnClose) y las automatizaciones "al
        // cerrar" (RunAutomationsOnTicketClosed) no corrían nunca desde el
        // flujo real — solo TicketService::closeTicket() (código muerto, sin
        // callers) lo disparaba correctamente. Mismo patrón que resolve()
        // debajo, que sí lo hacía bien.
        TicketClosed::dispatch($ticket);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.ticket_closed'),
                'ticket' => $ticket->fresh(),
            ]);
        }

        // Esta rama solo la usa el form clásico de la ficha completa (el panel
        // superpuesto de /tickets pasa por la rama JSON de arriba vía
        // execQuickAction) — se vuelve a la ficha completa, no al listado.
        return redirect()
            ->route('manager.helpdesk.tickets.show-full', $ticket)
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

        // Ver comentario en close(): solo la usa el form clásico de la ficha completa.
        return redirect()
            ->route('manager.helpdesk.tickets.show-full', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_resolved'));
    }

    public function reopen(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('reopen', $ticket);

        $ticket->reopen();

        // Mismo bug que close() arriba: sin esto SendCustomerReopenNotification
        // nunca notifica al cliente al reabrir desde la ficha real.
        TicketReopened::dispatch($ticket);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.ticket_reopened'),
                'ticket' => $ticket->fresh(),
            ]);
        }

        // Ver comentario en close(): solo la usa el form clásico de la ficha completa.
        return redirect()
            ->route('manager.helpdesk.tickets.show-full', $ticket)
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

        // merge() solo se dispara desde el form de la ficha completa.
        return redirect()->route('manager.helpdesk.tickets.show-full', $targetTicket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_merged', ['source' => $ticket->ticket_number, 'target' => $targetTicket->ticket_number]));
    }

    public function watch(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('watch', $ticket);

        // Por defecto uno se auto-sigue; añadir a OTRO compañero (modal
        // "Seguidores" del panel Gestión) exige permiso de gestión del
        // ticket, igual que asignar o vincular — TicketWatcher::addWatcher()
        // ya aceptaba cualquier user_id, solo faltaba el punto de entrada.
        $userId = $request->integer('user_id') ?: auth()->id();
        if ($userId !== auth()->id()) {
            $this->authorize('update', $ticket);
        }

        TicketWatcher::addWatcher($ticket->id, $userId);

        // Preferencias de aviso del modal "Seguidores": qué quiere ver quien
        // sigue el ticket. Solo se tocan si vienen en la petición, para que
        // un "seguir" simple conserve los valores por defecto (todo activo).
        if ($request->has('notify_customer_replies') || $request->has('notify_internal_notes')) {
            TicketWatcher::where('ticket_id', $ticket->id)
                ->where('user_id', $userId)
                ->update(array_filter([
                    'notify_customer_replies' => $request->has('notify_customer_replies')
                        ? $request->boolean('notify_customer_replies') : null,
                    'notify_internal_notes' => $request->has('notify_internal_notes')
                        ? $request->boolean('notify_internal_notes') : null,
                ], fn ($v) => $v !== null));
        }

        return response()->json(['watching' => true, 'message' => __('helpdesktickets::helpdesktickets.messages.ticket_watched')]);
    }

    public function unwatch(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('watch', $ticket);

        $userId = $request->integer('user_id') ?: auth()->id();
        if ($userId !== auth()->id()) {
            $this->authorize('update', $ticket);
        }

        TicketWatcher::removeWatcher($ticket->id, $userId);

        return response()->json(['watching' => false, 'message' => __('helpdesktickets::helpdesktickets.messages.ticket_unwatched')]);
    }

    public function snooze(SnoozeTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $until = $request->validated()['snoozed_until'];
        $ticket->update(['snoozed_until' => $until, 'snoozed_by' => auth()->id()]);

        // "Pausar el SLA mientras está aplazado": sin esto, un ticket
        // aplazado tres días seguía consumiendo su plazo de resolución y
        // aparecía vencido al volver, aunque nadie pudiera trabajarlo.
        // pauseSla() es idempotente, así que aplazar dos veces no acumula.
        if ($request->boolean('pause_sla')) {
            app(SlaService::class)->pauseSla($ticket);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket pospuesto.',
            'data' => [
                'snoozed_until' => $ticket->snoozed_until?->toIso8601String(),
                'sla_paused' => $ticket->fresh()->sla_paused_at !== null,
            ],
        ]);
    }

    public function unsnooze(Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $ticket->update(['snoozed_until' => null, 'snoozed_by' => null]);

        // Reanudar desplaza los vencimientos por el tiempo pausado, así que
        // el ticket vuelve con el plazo que le quedaba, no con el consumido.
        app(SlaService::class)->resumeSla($ticket);

        return response()->json(['success' => true, 'message' => 'Ticket reactivado.']);
    }

    /**
     * Devuelve JSON a quien lo pide (el modal 46 "Posible duplicado" y la
     * pestaña de tickets del panel) y sigue redirigiendo para el formulario
     * clásico. Sin esto, la llamada AJAX recibía un 302 hacia el referer, el
     * navegador lo seguía y se descargaba la página entera: la petición no
     * terminaba nunca y el modal se quedaba abierto sin decir nada.
     */
    public function linkTicket(LinkTicketRequest $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validated();

        if ($validated['linked_ticket_id'] == $ticket->id) {
            $message = 'No puedes enlazar un ticket consigo mismo.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->withErrors(['linked_ticket_id' => $message]);
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

        $message = __('helpdesktickets::helpdesktickets.settings.link.created');

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    public function unlinkTicket(Ticket $ticket, int $linkId): RedirectResponse
    {
        $this->authorize('update', $ticket);

        TicketLink::where('ticket_id', $ticket->id)
            ->where('id', $linkId)
            ->delete();

        return back()->with('success', __('helpdesktickets::helpdesktickets.settings.link.deleted'));
    }
}
