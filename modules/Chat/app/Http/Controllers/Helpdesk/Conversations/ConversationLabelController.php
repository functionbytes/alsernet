<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\Conversation;

class ConversationLabelController extends Controller
{
    /**
     * Add labels to conversation.
     */
    public function addLabels(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'labels' => 'required|array',
            'labels.*' => 'string|exists:chat_labels,title',
        ]);

        $conversation->addLabels($validated['labels']);

        return back()->with('success', 'Labels added successfully!');
    }

    /**
     * Update all labels for conversation (replaces existing).
     */
    public function updateLabels(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'labels' => 'nullable|array',
            'labels.*' => 'string|exists:chat_labels,title',
        ]);

        $labelsList = isset($validated['labels']) && count($validated['labels']) > 0
            ? implode(',', $validated['labels'])
            : null;

        $conversation->update(['cached_label_list' => $labelsList]);

        return back()->with('success', 'Labels updated successfully!');
    }

    /**
     * Remove label from conversation.
     */
    public function removeLabel(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'required|string',
        ]);

        $conversation->removeLabels([$validated['label']]);

        return back()->with('success', 'Label removed successfully!');
    }
}
