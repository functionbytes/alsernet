<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Chat\Events\ConversationAssigned;
use Modules\Chat\Events\ConversationUpdated;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationPriority;

class ConversationAssignmentController extends Controller
{
    /**
     * Assign conversation to a user.
     */
    public function assign(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $conversation->update(['assignee_id' => $validated['assignee_id']]);

        $assignedAgent = $validated['assignee_id'] ? User::find($validated['assignee_id']) : null;
        broadcast(new ConversationAssigned($conversation, $assignedAgent));

        return back()->with('success', 'Conversation assigned successfully!');
    }

    /**
     * Update conversation team.
     */
    public function updateTeam(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => 'nullable|exists:chat_teams,id',
        ]);

        $conversation->update(['team_id' => $validated['team_id']]);

        return back()->with('success', 'Team updated successfully!');
    }

    /**
     * Update conversation priority.
     */
    public function updatePriority(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => 'required|string',
        ]);

        $priority = ConversationPriority::forAccount($conversation->account_id)
            ->whereSlug($validated['priority'])
            ->firstOrFail();

        $conversation->update(['priority_id' => $priority->id]);

        broadcast(new ConversationUpdated($conversation, 'priority_changed'));

        return back()->with('success', 'Priority updated successfully!');
    }
}
