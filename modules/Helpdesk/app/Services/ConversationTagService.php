<?php

namespace Modules\Helpdesk\Services;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;

class ConversationTagService
{
    /**
     * Attach a tag to a conversation by ID (idempotent).
     */
    public function addTag(Conversation $conversation, int $tagId): ConversationTag
    {
        if (! $conversation->conversationTags()->where('tag_id', $tagId)->exists()) {
            $conversation->conversationTags()->attach($tagId);
        }

        return ConversationTag::findOrFail($tagId);
    }

    /**
     * Detach a tag from a conversation.
     */
    public function removeTag(Conversation $conversation, int $tagId): void
    {
        $conversation->conversationTags()->detach($tagId);
    }

    /**
     * Sync the conversation's tags to exactly the given set of IDs.
     */
    public function syncTags(Conversation $conversation, array $tagIds): void
    {
        $conversation->conversationTags()->sync($tagIds);
    }
}
