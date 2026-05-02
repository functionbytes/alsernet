<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskSocial\Contracts\AutoReplyEngineInterface;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Services\ConversationThreadingService;
use Modules\HelpdeskSocial\Services\CrisisModeService;
use Modules\HelpdeskSocial\Services\IntentClassificationService;
use Modules\HelpdeskSocial\Services\SentimentAnalysisService;
use Modules\HelpdeskSocial\Services\SlaTrackingService;
use Modules\HelpdeskSocial\Services\SmartAssignmentService;
use Modules\HelpdeskSocial\Services\SocialListeningService;

class ProcessSocialCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly array $event,
    ) {
        $this->onQueue(config('helpdesksocial.queues.processing', 'helpdesk-social-processing'));
    }

    public function handle(
        IntentClassificationService $classificationService,
        AutoReplyEngineInterface $responder,
        ConversationThreadingService $threadingService,
        SentimentAnalysisService $sentimentService,
        SlaTrackingService $slaService,
        SmartAssignmentService $assignmentService,
        SocialListeningService $listeningService,
        CrisisModeService $crisisService,
    ): void {
        $platform = $this->event['platform'];
        $commentId = $this->event['external_comment_id'];

        // Prevent duplicate processing
        $existing = SocialComment::where('platform', $platform)
            ->where('external_comment_id', $commentId)
            ->first();

        if ($existing) {
            return;
        }

        // Find the social account
        $account = SocialAccount::active()
            ->forPlatform($platform)
            ->where('external_id', $this->event['page_id'])
            ->first();

        if (! $account) {
            Log::warning('ProcessSocialCommentJob: No account found', [
                'platform' => $platform,
                'external_id' => $this->event['page_id'],
            ]);

            return;
        }

        // Skip if comments are disabled for this account
        if (! $account->comments_enabled) {
            return;
        }

        // Create the social comment
        $comment = SocialComment::create([
            'social_account_id' => $account->id,
            'platform' => $platform,
            'external_comment_id' => $commentId,
            'external_post_id' => $this->event['external_post_id'] ?? null,
            'external_parent_id' => $this->event['external_parent_id'] ?? null,
            'external_user_id' => $this->event['external_user_id'] ?? null,
            'author_name' => $this->event['author_name'] ?? 'Usuario',
            'author_username' => $this->event['author_username'] ?? null,
            'body' => $this->event['body'] ?? '',
            'is_mention' => $this->event['is_mention'] ?? false,
            'status' => 'pending',
            'posted_at' => $this->event['created_at'] ?? now(),
        ]);

        // Link to existing conversation or create a new one
        $threadingService->threadComment($comment);

        // Analyze sentiment
        $sentimentService->analyze($comment);

        // Apply SLA policy
        $slaService->applyPolicyToComment($comment);

        // Auto-assign if no manual assignment exists
        if (! $comment->assigned_to_user_id) {
            $assignmentService->assign($comment);
        }

        // Check for keyword matches via social listening
        $listeningService->scanComment($comment);

        // Broadcast new comment in real-time
        SocialCommentReceived::dispatch($comment);

        // Classify intent
        $classificationService->classify($comment);

        // Skip auto-reply if account is in crisis mode
        if ($crisisService->isInCrisisMode($account)) {
            Log::info('ProcessSocialCommentJob: Skipping auto-reply due to crisis mode', [
                'comment_id' => $comment->id,
                'account_id' => $account->id,
            ]);

            return;
        }

        // Evaluate auto-responder rules
        $result = $responder->evaluate($comment);

        if ($result && ($result['stop_processing'] ?? false)) {
            return;
        }

        // If not auto-replied and not spam, keep as pending for human review
        if ($comment->status === 'pending' && ! $comment->is_spam) {
            $this->createConversationFromComment($comment);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessSocialCommentJob failed permanently', [
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);
    }

    private function createConversationFromComment(SocialComment $comment): void
    {
        try {
            $safeEmail = 'social_'.md5($comment->external_user_id).'@social.local';
            $customer = Customer::firstOrCreate(
                ['email' => $safeEmail],
                [
                    'name' => $comment->author_name,
                    'facebook_psid' => $comment->platform === 'facebook' ? $comment->external_user_id : null,
                    'instagram_id' => $comment->platform === 'instagram' ? $comment->external_user_id : null,
                ]
            );

            $conversation = Conversation::create([
                'customer_id' => $customer->id,
                'channel' => $comment->platform,
                'external_sender_id' => $comment->external_user_id,
                'subject' => "Comentario en publicación {$comment->external_post_id}",
                'source' => 'social_comment',
            ]);

            ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $comment->body,
                'external_id' => $comment->external_comment_id,
                'metadata' => [
                    'social_comment_id' => $comment->id,
                    'post_id' => $comment->external_post_id,
                    'platform' => $comment->platform,
                ],
            ]);

            $comment->update(['helpdesk_conversation_id' => $conversation->id]);
        } catch (\Throwable $e) {
            Log::warning('ProcessSocialCommentJob: Failed to create conversation', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
