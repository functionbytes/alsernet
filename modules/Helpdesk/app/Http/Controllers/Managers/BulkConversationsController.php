<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;

class BulkConversationsController extends Controller
{
    /**
     * Handle bulk conversation operations.
     *
     * Actions: assign, close, reopen, archive, delete
     */
    public function handle(Request $request): RedirectResponse
    {
        $this->authorize('helpdesk.conversations.manage');

        $validated = $request->validate([
            'conversation_ids' => 'required|array|min:1|max:100',
            'conversation_ids.*' => 'integer',
            'action' => 'required|string|in:assign,close,reopen,archive,delete',
            'agent_id' => 'required_if:action,assign|nullable|integer|exists:users,id',
        ]);

        $ids = $validated['conversation_ids'];

        try {
            $count = DB::transaction(function () use ($validated, $ids): int {
                return match ($validated['action']) {
                    'assign' => Conversation::whereIn('id', $ids)->update([
                        'assignee_id' => $validated['agent_id'],
                        'assigned_at' => $validated['agent_id'] ? now() : null,
                        'updated_at' => now(),
                    ]),
                    'close' => Conversation::whereIn('id', $ids)
                        ->whereNull('closed_at')
                        ->update([
                            'closed_at' => now(),
                            'updated_at' => now(),
                        ]),
                    'reopen' => Conversation::whereIn('id', $ids)
                        ->whereNotNull('closed_at')
                        ->update([
                            'closed_at' => null,
                            'updated_at' => now(),
                        ]),
                    'archive' => Conversation::whereIn('id', $ids)->update([
                        'is_archived' => true,
                        'updated_at' => now(),
                    ]),
                    'delete' => Conversation::whereIn('id', $ids)->delete(),
                };
            });

            session()->flash('success', "{$count} conversaciones actualizadas correctamente.");
        } catch (\Throwable $e) {
            Log::error('Bulk conversation operation failed', [
                'action' => $validated['action'],
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'No se pudo completar la operación. Por favor intenta de nuevo.');

            return redirect()->back();
        }

        return redirect()->route('manager.helpdesk.conversations.index');
    }
}
