<?php

namespace Modules\HelpdeskSocial\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;

class SyncSocialCommentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 15;

    public function __construct(
        public readonly int $accountId,
        public readonly ?string $postId = null,
    ) {
        $this->onQueue(config('helpdesksocial.queues.processing', 'helpdesk-social-processing'));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncSocialCommentsJob failed permanently', [
            'account_id' => $this->accountId,
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
        ]);
    }

    public function handle(SocialApiClientInterface $apiClient): void
    {
        if (! helpdesk_social_enabled()) {
            return;
        }

        $account = SocialAccount::find($this->accountId);

        if (! $account || ! $account->is_active || ! $account->comments_enabled) {
            return;
        }

        try {
            if ($this->postId) {
                $this->syncPostComments($account, $this->postId, $apiClient);
            } else {
                $this->syncKnownPosts($account, $apiClient);
            }

            $account->recordSuccess();
            $account->update([
                'last_synced_at' => now(),
                'last_error_at' => null,
                'last_error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $account->recordFailure();
            $account->update([
                'last_error_at' => now(),
                'last_error_message' => $e->getMessage(),
            ]);

            Log::error('SyncSocialCommentsJob failed', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function syncPostComments(SocialAccount $account, string $postId, SocialApiClientInterface $apiClient): void
    {
        $comments = $apiClient->getComments($postId, $account->page_access_token, 100);

        if ($comments === []) {
            return;
        }

        // Antes: 1 SELECT de existencia por comentario (hasta 100 por post,
        // hasta 2000 en una sola ejecución de syncKnownPosts() con 20 posts) —
        // ahora un único whereIn() precarga los external_comment_id ya
        // existentes de ESTE lote, usando el mismo índice único
        // (platform, external_comment_id) en una sola ida a BD.
        $externalIds = array_column($comments, 'id');
        $existingIds = SocialComment::where('platform', $account->platform)
            ->whereIn('external_comment_id', $externalIds)
            ->pluck('external_comment_id')
            ->all();
        $existingIds = array_flip($existingIds);

        foreach ($comments as $commentData) {
            $externalId = $commentData['id'];

            if (isset($existingIds[$externalId])) {
                continue;
            }

            $comment = SocialComment::create([
                'social_account_id' => $account->id,
                'platform' => $account->platform,
                'external_comment_id' => $externalId,
                'external_post_id' => $postId,
                'external_parent_id' => $commentData['parent']['id'] ?? null,
                'external_user_id' => $commentData['from']['id'] ?? null,
                'author_name' => $commentData['from']['name'] ?? 'Usuario',
                'body' => $commentData['message'] ?? '',
                'status' => 'pending',
                'posted_at' => isset($commentData['created_time']) ? Carbon::parse($commentData['created_time']) : now(),
            ]);

            SocialCommentReceived::dispatch($comment);
        }
    }

    /**
     * Fallback de polling: re-sincroniza los posts que ya conocemos de la cuenta
     * para recuperar comentarios cuyo webhook no se haya entregado. No descubre
     * posts sin actividad previa — para eso está el webhook en tiempo real.
     */
    private function syncKnownPosts(SocialAccount $account, SocialApiClientInterface $apiClient): void
    {
        $postIds = SocialComment::query()
            ->where('social_account_id', $account->id)
            ->whereNotNull('external_post_id')
            ->groupBy('external_post_id')
            ->orderByRaw('MAX(posted_at) DESC')
            ->limit((int) config('helpdesksocial.comments.max_posts_per_sync', 20))
            ->pluck('external_post_id');

        foreach ($postIds as $postId) {
            $this->syncPostComments($account, $postId, $apiClient);
        }
    }
}
