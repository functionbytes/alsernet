<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Events\TicketResolved;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\SpamClassifierService;
use Modules\HelpdeskTickets\Services\TicketMergeService;
use Modules\HelpdeskTickets\Services\TicketUpdateService;
use Throwable;

class BulkTicketsController extends Controller
{
    public function __construct(
        private readonly TicketMergeService $merger,
        private readonly TicketMailsController $mailsController,
        private readonly TicketUpdateService $updates,
    ) {}

    /**
     * Ability de TicketPolicy que autoriza cada acción masiva. La
     * autorización real es por ticket (igual que
     * TicketMessagingController::bulkReply): los tickets que el usuario no
     * puede actuar se omiten en vez de abortar toda la operación.
     *
     * @var array<string, string>
     */
    private const ABILITY_BY_ACTION = [
        'assign' => 'assign',
        'close' => 'close',
        // Cerrar como spam (24-sep-2026): mismo permiso que cerrar.
        'mark_spam' => 'close',
        'resolve' => 'resolve',
        'reopen' => 'reopen',
        'change_status' => 'update',
        'change_priority' => 'update',
        'delete' => 'delete',
        'add_tag' => 'update',
        'remove_tag' => 'update',
        'snooze' => 'update',
        'assign_group' => 'update',
        // "Vincular a un ticket" del mockup: mismo permiso que la fusión
        // individual (merge() en TicketLifecycleController).
        'link_to_ticket' => 'merge',
        'retry_failed_mail' => 'update',
    ];

    /**
     * Handle bulk ticket operations.
     *
     * Actions: assign, close, resolve, reopen, change_status, delete, add_tag,
     * assign_group, link_to_ticket, retry_failed_mail
     *
     * Este endpoint solo se consume por AJAX desde tickets.js (bulk-bar del
     * listado), por eso responde siempre en JSON en vez de redirect: un
     * redirect aquí rompe el `dataType: 'json'` del cliente (jQuery sigue la
     * redirección, intenta parsear el HTML resultante como JSON y dispara
     * `.fail()` aunque la operación haya tenido éxito en el servidor).
     */
    public function handle(BulkTicketRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $validated = $request->validated();
        $ids = $validated['ticket_ids'];
        $action = $validated['action'];

        $tickets = Ticket::whereIn('id', $ids)->get();

        [$authorized, $skipped] = $tickets->partition(
            fn (Ticket $ticket) => $request->user()->can(self::ABILITY_BY_ACTION[$action], $ticket)
        );

        // "Vincular a un ticket": el destino necesita permiso de EDICIÓN
        // propio (igual que el merge individual, que exige update() sobre el
        // ticket destino además de merge() sobre cada origen), y se excluye
        // de la propia selección -- fusionar un ticket consigo mismo no
        // tiene sentido y $merger->merge() lo borraría.
        $targetTicket = null;
        if ($action === 'link_to_ticket') {
            $targetTicket = Ticket::findOrFail($validated['merge_into_id']);
            $this->authorize('update', $targetTicket);
            $authorized = $authorized->reject(fn (Ticket $ticket) => $ticket->is($targetTicket));
        }

        if ($authorized->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para actuar sobre los tickets seleccionados.',
                'skipped_ticket_ids' => $skipped->pluck('id')->values(),
            ], 403);
        }

        try {
            // Todas las ramas iteran modelos (no mass update/delete del builder)
            // para que TicketObserver registre historial y bumpee la caché de
            // reportes igual que en las acciones individuales.
            $actor = $request->user();
            $count = DB::transaction(function () use ($action, $validated, $authorized, $targetTicket, $actor): int {
                return match ($action) {
                    // Antes hacía update() directo: no creaba el item de
                    // actividad ni disparaba TicketAssigned (a diferencia de
                    // la asignación individual vía assignTo()), así que una
                    // reasignación masiva no notificaba a los agentes ni
                    // encadenaba automatizaciones "al asignar".
                    'assign' => $authorized
                        ->each(function (Ticket $ticket) use ($validated): void {
                            $ticket->assignTo($validated['agent_id']);

                            $agent = User::find($validated['agent_id']);
                            if ($agent) {
                                TicketAssigned::dispatch($ticket, $agent);
                            }
                        })
                        ->count(),
                    // Mismo bug que el botón individual "Cerrar ticket": sin
                    // TicketClosed, la encuesta CSAT automática no se enviaba
                    // al cerrar en bloque.
                    'close' => $authorized
                        ->whereNull('closed_at')
                        ->each(function (Ticket $ticket): void {
                            $previous = $ticket->status;
                            $ticket->close();
                            $this->dispatchStatusChange($ticket, $previous);
                            TicketClosed::dispatch($ticket);
                        })
                        ->count(),
                    // Boletines y spam que ya entraron como ticket: se cierran
                    // con motivo 'spam' y sin encuesta, y (con permiso de
                    // ajustes) el remitente pasa a la lista negra para que el
                    // siguiente ni llegue. SpamClassifierService deja además de
                    // tratar como "cliente conocido" a quien solo tiene tickets
                    // cerrados como spam.
                    'mark_spam' => $authorized
                        ->whereNull('closed_at')
                        ->each(function (Ticket $ticket) use ($validated, $actor): void {
                            $previous = $ticket->status;
                            $ticket->close('spam', ['skip_survey' => true]);
                            $this->dispatchStatusChange($ticket, $previous);
                            TicketClosed::dispatch($ticket);

                            if (! empty($validated['block_senders']) && $actor->can('helpdesk.tickets.settings')) {
                                $this->blockSender($ticket, $actor);
                            }
                        })
                        ->count(),
                    // Solo los que siguen abiertos: antes pasaba a "Resuelto"
                    // también tickets ya cerrados o ya resueltos.
                    'resolve' => $authorized
                        ->filter(fn (Ticket $ticket) => $ticket->closed_at === null && $ticket->resolved_at === null)
                        ->each(function (Ticket $ticket): void {
                            $previous = $ticket->status;
                            $ticket->resolve();
                            $this->dispatchStatusChange($ticket, $previous);
                            TicketResolved::dispatch($ticket);
                        })
                        ->count(),
                    // Mismo bug que el botón individual "Reabrir": sin
                    // TicketReopened no se notifica al cliente al reabrir en bloque.
                    // Cerrados Y resueltos, como el botón individual: antes
                    // exigía closed_at y un ticket resuelto no se reabría.
                    'reopen' => $authorized
                        ->filter(fn (Ticket $ticket) => $ticket->closed_at !== null || $ticket->resolved_at !== null)
                        ->each(function (Ticket $ticket): void {
                            $previous = $ticket->status;
                            $ticket->reopen();
                            $this->dispatchStatusChange($ticket, $previous);
                            TicketReopened::dispatch($ticket);
                        })
                        ->count(),
                    // Mismo servicio que el selector de estado de la ficha:
                    // pausa/reanuda el SLA, deja rastro en el hilo y dispara
                    // TicketStatusChanged (aviso al cliente, automatizaciones).
                    // Antes era un update() directo sin nada de eso.
                    'change_status' => $authorized
                        ->each(fn (Ticket $ticket) => $this->updates->applyChanges(
                            $ticket,
                            ['status_id' => $validated['status_id']],
                            $actor,
                        ))
                        ->count(),
                    // Prioridad por el mismo servicio que la ficha: deja rastro
                    // en el hilo y recalcula el SLA con su multiplicador.
                    'change_priority' => $authorized
                        ->each(fn (Ticket $ticket) => $this->updates->applyChanges(
                            $ticket,
                            ['priority' => $validated['priority']],
                            $actor,
                        ))
                        ->count(),
                    'delete' => $authorized
                        ->each(fn (Ticket $ticket) => $ticket->delete())
                        ->count(),
                    'remove_tag' => $authorized
                        ->filter(fn (Ticket $ticket) => in_array($validated['tag'], $ticket->tags ?? [], true))
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'tags' => array_values(array_diff($ticket->tags ?? [], [$validated['tag']])),
                        ]))
                        ->count(),
                    // Igual que "Posponer" individual sin pausar el SLA: el
                    // ticket sale de la cola hasta la fecha y vuelve solo (o
                    // antes, si el cliente responde).
                    'snooze' => $authorized
                        ->whereNull('closed_at')
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'snoozed_until' => now()->addHours((int) $validated['snooze_hours']),
                            'snoozed_by' => $actor->id,
                        ]))
                        ->count(),
                    'add_tag' => $authorized
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'tags' => array_values(array_unique(array_merge($ticket->tags ?? [], [$validated['tag']]))),
                        ]))
                        ->count(),
                    'assign_group' => $authorized
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'group_id' => $validated['group_id'],
                        ]))
                        ->count(),
                    'link_to_ticket' => $authorized
                        ->each(fn (Ticket $ticket) => $this->merger->merge($ticket, $targetTicket))
                        ->count(),
                    // Solo reintenta los tickets que de verdad tengan un
                    // correo de SALIDA fallido -- reusa la misma ruta que el
                    // botón individual "Reenviar" (TicketMailsController::
                    // resend()) en vez de duplicar su lógica de destinatario/
                    // adjuntos reenviables. Un fallo puntual en un ticket no
                    // aborta el resto: se registra y se sigue con los demás,
                    // igual que el resto de acciones masivas con el 403 por
                    // ticket ya filtrado más arriba.
                    'retry_failed_mail' => $authorized
                        ->filter(fn (Ticket $ticket) => $ticket->mails()->where('direction', 'outbound')->where('status', 'failed')->exists())
                        ->each(function (Ticket $ticket): void {
                            $ticket->mails()->where('direction', 'outbound')->where('status', 'failed')->get()
                                ->each(function (TicketMail $mail): void {
                                    try {
                                        $this->mailsController->resend(new Request, $mail);
                                    } catch (Throwable $e) {
                                        Log::error('Bulk retry_failed_mail: no se pudo reenviar', [
                                            'mail_id' => $mail->id,
                                            'ticket_id' => $mail->ticket_id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                });
                        })
                        ->count(),
                };
            });
        } catch (Throwable $e) {
            Log::error('Bulk ticket operation failed', [
                'action' => $action,
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The bulk operation could not be completed. Please try again.',
            ], 500);
        }

        $message = "{$count} tickets updated successfully.";
        if ($skipped->isNotEmpty()) {
            $message .= " {$skipped->count()} omitidos por falta de permiso.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'updated_count' => $count,
            'skipped_ticket_ids' => $skipped->pluck('id')->values(),
        ]);
    }

    /**
     * Tickets abiertos que parecen boletines o envíos automáticos, para
     * seleccionarlos y cerrarlos como spam de una vez. Mismo criterio que la
     * cuarentena de la ingesta (SpamClassifierService::bulkReason), aplicado
     * a las cabeceras del correo original guardado (raw_email): los tickets
     * que entraron antes de que existiera la cuarentena, o de remitentes que
     * ya tenían tickets.
     */
    public function bulkMailCandidates(Request $request, SpamClassifierService $classifier): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $ticketIds = Ticket::query()
            ->visibleToAgent($request->user())
            ->whereNull('closed_at')
            ->whereNull('resolved_at')
            ->latest('id')
            ->limit(500)
            ->pluck('id');

        // Solo la cabecera: raw_email puede pesar cientos de KB por correo.
        $firstInbound = TicketMail::query()
            ->whereIn('ticket_id', $ticketIds)
            ->where('direction', 'inbound')
            ->whereNotNull('raw_email')
            ->orderBy('id')
            ->selectRaw('ticket_id, SUBSTRING(raw_email, 1, 16384) as head')
            ->get()
            ->unique('ticket_id');

        $reasons = [];
        foreach ($firstInbound as $mail) {
            $reason = $classifier->bulkReason(self::parseHeaders((string) $mail->head));
            if ($reason !== null) {
                $reasons[$mail->ticket_id] = $reason;
            }
        }

        return response()->json([
            'ids' => array_keys($reasons),
            'reasons' => $reasons,
            'can_block_senders' => $request->user()->can('helpdesk.tickets.settings'),
        ]);
    }

    /**
     * Cabeceras de un correo en crudo (RFC 5322): hasta la primera línea en
     * blanco, desplegando las líneas de continuación. Gana la primera
     * aparición de cada nombre, como en bulkReason().
     *
     * @return array<string, string>
     */
    public static function parseHeaders(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $end = strpos($raw, "\n\n");
        $block = $end === false ? $raw : substr($raw, 0, $end);
        $block = preg_replace("/\n[ \t]+/", ' ', $block) ?? $block;

        $headers = [];
        foreach (explode("\n", $block) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $name = implode('-', array_map('ucfirst', explode('-', strtolower(trim($name)))));
            $headers[$name] ??= trim($value);
        }

        return $headers;
    }

    private function blockSender(Ticket $ticket, User $actor): void
    {
        $email = strtolower(trim((string) $ticket->customer?->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        TicketEmailBlacklist::query()->firstOrCreate(
            ['type' => 'email', 'value' => $email],
            [
                'reason' => 'Marcado como spam desde '.$ticket->ticket_number,
                'is_active' => true,
                'added_by' => $actor->id,
            ],
        );
    }

    /**
     * Igual que TicketLifecycleController::broadcastStatusChange(): cerrar,
     * resolver o reabrir en bloque también es un cambio de estado (aviso al
     * cliente, historial, automatizaciones "al cambiar estado").
     */
    private function dispatchStatusChange(Ticket $ticket, $previousStatus): void
    {
        $fresh = $ticket->fresh(['customer', 'status', 'category', 'assignee']);

        if ($fresh && $previousStatus && $fresh->status && $previousStatus->id !== $fresh->status->id) {
            TicketStatusChanged::dispatch($fresh, $previousStatus, $fresh->status);
        }
    }
}
