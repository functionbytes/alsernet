<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketResolved;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;

class BulkTicketsController extends Controller
{
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
        'resolve' => 'resolve',
        'reopen' => 'reopen',
        'change_status' => 'update',
        'delete' => 'delete',
        'add_tag' => 'update',
        'assign_group' => 'update',
    ];

    /**
     * Handle bulk ticket operations.
     *
     * Actions: assign, close, resolve, reopen, change_status, delete, add_tag, assign_group
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
            $count = DB::transaction(function () use ($action, $validated, $authorized): int {
                return match ($action) {
                    'assign' => $authorized
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'assignee_id' => $validated['agent_id'],
                            'assigned_at' => now(),
                        ]))
                        ->count(),
                    'close' => $authorized
                        ->whereNull('closed_at')
                        ->each(fn (Ticket $ticket) => $ticket->close())
                        ->count(),
                    'resolve' => $authorized
                        ->each(function (Ticket $ticket): void {
                            $ticket->resolve();
                            TicketResolved::dispatch($ticket);
                        })
                        ->count(),
                    'reopen' => $authorized
                        ->whereNotNull('closed_at')
                        ->each(fn (Ticket $ticket) => $ticket->reopen())
                        ->count(),
                    'change_status' => $authorized
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'status_id' => $validated['status_id'],
                        ]))
                        ->count(),
                    'delete' => $authorized
                        ->each(fn (Ticket $ticket) => $ticket->delete())
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
                };
            });
        } catch (\Throwable $e) {
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
}
