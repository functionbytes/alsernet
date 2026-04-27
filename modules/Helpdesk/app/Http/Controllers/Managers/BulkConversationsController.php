<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Http\Requests\BulkConversationActionRequest;
use Modules\Helpdesk\Models\Conversation;

class BulkConversationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.conversations.update');
    }

    /**
     * Handle bulk conversation operations.
     *
     * Actions: assign, close, reopen, archive, delete
     */
    public function handle(BulkConversationActionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
