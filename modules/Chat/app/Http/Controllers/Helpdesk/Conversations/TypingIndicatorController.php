<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Events\UserTyping;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\Conversation;

class TypingIndicatorController extends Controller
{
    /**
     * Broadcast typing indicator.
     */
    public function __invoke(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'typing' => 'required|boolean',
        ]);

        // Broadcast typing event
        broadcast(new UserTyping(
            $conversation->id,
            $request->user(),
            $validated['typing']
        ));

        return response()->json(['success' => true]);
    }
}
