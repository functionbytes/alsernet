<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;

class BulkTicketsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.manage');
    }

    /**
     * Handle bulk ticket operations.
     *
     * Actions: assign, close, reopen, change_status, delete, add_tag, assign_group
     */
    public function handle(BulkTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ids = $validated['ticket_ids'];

        try {
            // Todas las ramas iteran modelos (no mass update/delete del builder)
            // para que TicketObserver registre historial y bumpee la caché de
            // reportes igual que en las acciones individuales.
            $count = DB::transaction(function () use ($validated, $ids): int {
                return match ($validated['action']) {
                    'assign' => Ticket::whereIn('id', $ids)
                        ->get()
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'assignee_id' => $validated['agent_id'],
                            'assigned_at' => now(),
                        ]))
                        ->count(),
                    'close' => Ticket::whereIn('id', $ids)
                        ->whereNull('closed_at')
                        ->get()
                        ->each(fn (Ticket $ticket) => $ticket->close())
                        ->count(),
                    'reopen' => Ticket::whereIn('id', $ids)
                        ->whereNotNull('closed_at')
                        ->get()
                        ->each(fn (Ticket $ticket) => $ticket->reopen())
                        ->count(),
                    'change_status' => Ticket::whereIn('id', $ids)
                        ->get()
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'status_id' => $validated['status_id'],
                        ]))
                        ->count(),
                    'delete' => Ticket::whereIn('id', $ids)
                        ->get()
                        ->each(fn (Ticket $ticket) => $ticket->delete())
                        ->count(),
                    'add_tag' => Ticket::whereIn('id', $ids)
                        ->get()
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'tags' => array_values(array_unique(array_merge($ticket->tags ?? [], [$validated['tag']]))),
                        ]))
                        ->count(),
                    'assign_group' => Ticket::whereIn('id', $ids)
                        ->get()
                        ->each(fn (Ticket $ticket) => $ticket->update([
                            'group_id' => $validated['group_id'],
                        ]))
                        ->count(),
                };
            });

            session()->flash('success', "{$count} tickets updated successfully.");
        } catch (\Throwable $e) {
            Log::error('Bulk ticket operation failed', [
                'action' => $validated['action'],
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'The bulk operation could not be completed. Please try again.');

            return redirect()->back();
        }

        return redirect()->route('manager.helpdesk.tickets.index');
    }
}
