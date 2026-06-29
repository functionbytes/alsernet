<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialConversation;

class ConversationThreadingService
{
    /**
     * Find or create a SocialConversation for a comment and link it.
     */
    public function threadComment(SocialComment $comment): SocialConversation
    {
        $conversation = $this->findOrCreateConversation($comment);

        if ($comment->social_conversation_id !== $conversation->id) {
            $comment->update(['social_conversation_id' => $conversation->id]);
        }

        $this->updateConversationStats($conversation);

        return $conversation;
    }

    /**
     * Find an existing conversation for a comment or create a new one.
     */
    public function findOrCreateConversation(SocialComment $comment): SocialConversation
    {
        $participantId = $comment->external_user_id;
        $platform = $comment->platform;
        $accountId = $comment->social_account_id;
        $postId = $comment->external_post_id;

        if (! $participantId || ! $accountId) {
            return $this->createConversation($comment);
        }

        $conversation = SocialConversation::query()
            ->where('social_account_id', $accountId)
            ->where('platform', $platform)
            ->where('participant_external_id', $participantId)
            ->when($postId, fn ($q) => $q->where('external_post_id', $postId))
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return $this->createConversation($comment);
    }

    /**
     * Link a comment to an existing conversation.
     */
    public function linkToConversation(SocialComment $comment, SocialConversation $conversation): void
    {
        $comment->update(['social_conversation_id' => $conversation->id]);

        $this->updateConversationStats($conversation);

        Log::info('ConversationThreadingService: comment linked', [
            'comment_id' => $comment->id,
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Update conversation message count and last message timestamp.
     */
    public function updateConversationStats(SocialConversation $conversation): void
    {
        $count = $conversation->comments()->count();
        $lastComment = $conversation->comments()->latest('posted_at')->first();

        $conversation->update([
            'message_count' => $count,
            'last_message_at' => $lastComment?->posted_at ?? $lastComment?->created_at ?? now(),
        ]);
    }

    /**
     * Close a conversation.
     */
    public function closeConversation(SocialConversation $conversation): void
    {
        $conversation->update(['status' => 'closed']);
    }

    /**
     * Reopen a conversation.
     */
    public function reopenConversation(SocialConversation $conversation): void
    {
        $conversation->update(['status' => 'open']);
    }

    private function createConversation(SocialComment $comment): SocialConversation
    {
        $conversation = SocialConversation::create([
            'social_account_id' => $comment->social_account_id,
            'platform' => $comment->platform,
            'external_post_id' => $comment->external_post_id,
            'participant_external_id' => $comment->external_user_id,
            'participant_name' => $comment->author_name,
            'status' => 'open',
            'message_count' => 1,
            'last_message_at' => $comment->posted_at ?? now(),
        ]);

        Log::info('ConversationThreadingService: conversation created', [
            'conversation_id' => $conversation->id,
            'comment_id' => $comment->id,
            'participant_external_id' => $comment->external_user_id,
        ]);

        return $conversation;
    }
}
