<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use Illuminate\Http\RedirectResponse;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Macro;
use Modules\Chat\Services\Macros\MacroExecutor;

class ConversationMacroController extends Controller
{
    /**
     * Execute a macro on a conversation
     */
    public function execute(Conversation $conversation, Macro $macro, MacroExecutor $executor): RedirectResponse
    {
        \Log::debug('[MacroExecutor] Starting macro execution', [
            'conversation_id' => $conversation->id,
            'macro_id' => $macro->id,
            'macro_name' => $macro->name,
        ]);

        // Verify conversation belongs to the user's account
        if ($conversation->account_id !== auth()->user()->account_id) {
            abort(403, 'Unauthorized access to this conversation');
        }

        // Verify macro belongs to the same account
        if ($macro->account_id !== auth()->user()->account_id) {
            abort(403, 'Unauthorized access to this macro');
        }

        // Execute the macro
        $results = $executor->execute($macro, $conversation);

        \Log::debug('[MacroExecutor] Execution results', [
            'results' => $results,
        ]);

        // Check if all actions succeeded
        $allSuccessful = collect($results)->every(fn ($r) => $r['success'] ?? false);

        if ($allSuccessful) {
            \Log::info('[MacroExecutor] Macro executed successfully', ['macro_id' => $macro->id]);

            return back()->with('success', "Macro '{$macro->name}' executed successfully!");
        } else {
            $errors = collect($results)->filter(fn ($r) => ! ($r['success'] ?? false))->pluck('error');
            \Log::warning('[MacroExecutor] Some actions failed', ['errors' => $errors->toArray()]);

            return back()->with('error', 'Some macro actions failed: '.$errors->implode(', '));
        }
    }
}
