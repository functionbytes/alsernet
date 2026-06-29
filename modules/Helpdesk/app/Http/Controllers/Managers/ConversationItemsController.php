<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\ToggleReactionRequest;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationItemsController extends Controller
{
    /**
     * Toggle an emoji reaction on a conversation item.
     * Adds the reaction if the user hasn't reacted with that emoji yet,
     * removes it if they have.
     */
    public function react(ToggleReactionRequest $request, ConversationItem $item): JsonResponse
    {
        $this->authorize('view', $item->conversation);

        $emoji = $request->validated()['emoji'];
        $userId = auth()->id();

        $reactions = $item->metadata['reactions'] ?? [];

        $existingIndex = null;
        foreach ($reactions as $index => $reaction) {
            if ($reaction['user_id'] === $userId && $reaction['emoji'] === $emoji) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            array_splice($reactions, $existingIndex, 1);
        } else {
            $reactions[] = [
                'user_id' => $userId,
                'emoji' => $emoji,
                'at' => now()->toIso8601String(),
            ];
        }

        $item->metadata = array_merge($item->metadata ?? [], ['reactions' => $reactions]);
        $item->save();

        $grouped = $this->groupReactions($reactions);

        return response()->json([
            'success' => true,
            'reactions' => $grouped,
        ]);
    }

    /**
     * Soft-delete a conversation item (e.g. an internal note).
     */
    public function destroy(ConversationItem $item): JsonResponse
    {
        $this->authorize('update', $item->conversation);

        $item->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Group reactions by emoji with counts and current user flag.
     */
    private function groupReactions(array $reactions): array
    {
        $userId = auth()->id();
        $grouped = [];

        foreach ($reactions as $reaction) {
            $emoji = $reaction['emoji'];

            if (! isset($grouped[$emoji])) {
                $grouped[$emoji] = ['emoji' => $emoji, 'count' => 0, 'reacted' => false];
            }

            $grouped[$emoji]['count']++;

            if ($reaction['user_id'] === $userId) {
                $grouped[$emoji]['reacted'] = true;
            }
        }

        return array_values($grouped);
    }
}
