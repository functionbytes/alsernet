<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;

class BulkTicketsController extends Controller
{
    /**
     * Handle bulk ticket operations.
     *
     * Actions: assign, close, reopen, change_status, delete
     */
    public function handle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|array|min:1|max:100',
            'ticket_ids.*' => 'integer',
            'action' => 'required|string|in:assign,close,reopen,change_status,delete',
            'agent_id' => 'required_if:action,assign|nullable|integer|exists:users,id',
            'status_id' => 'required_if:action,change_status|nullable|integer|exists:helpdesk_ticket_statuses,id',
        ]);

        $ids = $validated['ticket_ids'];

        try {
            $count = DB::transaction(function () use ($validated, $ids): int {
                return match ($validated['action']) {
                    'assign' => Ticket::whereIn('id', $ids)->update([
                        'assignee_id' => $validated['agent_id'],
                        'assigned_at' => now(),
                        'updated_at' => now(),
                    ]),
                    'close' => Ticket::whereIn('id', $ids)
                        ->whereNull('closed_at')
                        ->update([
                            'closed_at' => now(),
                            'updated_at' => now(),
                        ]),
                    'reopen' => Ticket::whereIn('id', $ids)
                        ->whereNotNull('closed_at')
                        ->update([
                            'closed_at' => null,
                            'updated_at' => now(),
                        ]),
                    'change_status' => Ticket::whereIn('id', $ids)->update([
                        'status_id' => $validated['status_id'],
                        'updated_at' => now(),
                    ]),
                    'delete' => Ticket::whereIn('id', $ids)->delete(),
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
