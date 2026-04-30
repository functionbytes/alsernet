<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Chat\Events\ConversationStatusChanged;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Jobs\SendCsatSurvey;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationStatus;

class ConversationStatusController extends Controller
{
    /**
     * Update the conversation status.
     */
    public function updateStatus(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $status = ConversationStatus::forAccount($conversation->account_id)
            ->whereSlug($validated['status'])
            ->firstOrFail();

        $oldStatus = $conversation->status?->slug ?? $conversation->status;
        $conversation->update(['status_id' => $status->id]);

        if ($validated['status'] === 'resolved' && $oldStatus !== 'resolved') {
            SendCsatSurvey::dispatch($conversation);
        }

        broadcast(new ConversationStatusChanged($conversation, $oldStatus, $validated['status']));

        return back()->with('success', 'Conversation status updated!');
    }

    /**
     * Close the conversation.
     */
    public function close(Conversation $conversation): RedirectResponse
    {
        $conversation->close();

        broadcast(new ConversationStatusChanged(
            $conversation,
            $conversation->getOriginal('status'),
            'closed'
        ));

        return back()->with('success', 'Conversation closed successfully!');
    }

    /**
     * Reopen the conversation.
     */
    public function reopen(Conversation $conversation): RedirectResponse
    {
        $conversation->reopen();

        broadcast(new ConversationStatusChanged(
            $conversation,
            $conversation->getOriginal('status'),
            'open'
        ));

        return back()->with('success', 'Conversation reopened successfully!');
    }

    /**
     * Snooze conversation.
     */
    public function snooze(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'snoozed_until' => 'required|date|after:now',
        ]);

        $conversation->snooze(new \DateTime($validated['snoozed_until']));

        return back()->with('success', 'Conversation snoozed successfully!');
    }

    /**
     * Unsnooze conversation.
     */
    public function unsnooze(Conversation $conversation): RedirectResponse
    {
        $conversation->unsnooze();

        return back()->with('success', 'Conversation unsnoozed!');
    }
}
