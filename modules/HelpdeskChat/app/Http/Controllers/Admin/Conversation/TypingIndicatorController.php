<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Conversation;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskChat\Events\UserTyping;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

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
