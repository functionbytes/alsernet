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
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Services\CrisisModeService;

/**
 * Evalua las reglas de auto-respuesta de un comentario (requiere que la
 * intencion ya haya sido clasificada por ClassifyIntentJob, del cual depende
 * mediante Bus::chain) y, si nadie respondio automaticamente, crea la
 * conversacion de Helpdesk para revision humana.
 */
class EvaluateAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    public function __construct(
        public readonly int $commentId,
    ) {
        $this->onQueue(config('helpdesksocial.queues.ai', 'helpdesk-social-ai'));
    }

    public function handle(
        AutoReplyEngineInterface $responder,
        CrisisModeService $crisisService,
    ): void {
        $comment = SocialComment::find($this->commentId);

        if (! $comment || ! helpdesk_social_enabled()) {
            return;
        }

        $account = $comment->socialAccount;

        // Skip auto-reply if the account is in crisis mode
        if ($account && $crisisService->isInCrisisMode($account)) {
            Log::info('EvaluateAutoReplyJob: Skipping auto-reply due to crisis mode', [
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
        Log::error('EvaluateAutoReplyJob failed permanently', [
            'comment_id' => $this->commentId,
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
            Log::warning('EvaluateAutoReplyJob: Failed to create conversation', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
