<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
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

        // Pre-filtra en bloque los ya conocidos con un único whereIn() (mismo
        // índice único platform+external_comment_id) para no despachar un job
        // por cada comentario que este fallback de polling ya vio en una
        // sincronización anterior. ProcessSocialCommentJob hace su propia
        // comprobación de dedupe final (por si hay una carrera), así que este
        // filtro es solo una optimización, no la fuente de verdad.
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

            // Despacha por el mismo pipeline que el webhook (dedupe, threading,
            // SLA, asignación, clasificación) en vez de crear el SocialComment a
            // pelo — si no, este fallback de polling deja los comentarios sin
            // SLA/hilo/asignación/clasificación.
            ProcessSocialCommentJob::dispatch([
                'platform' => $account->platform,
                'page_id' => $account->external_id,
                'external_comment_id' => $externalId,
                'external_post_id' => $postId,
                'external_parent_id' => $commentData['parent']['id'] ?? null,
                'external_user_id' => $commentData['from']['id'] ?? null,
                'author_name' => $commentData['from']['name'] ?? 'Usuario',
                'body' => $commentData['message'] ?? '',
                'is_mention' => false,
                'created_at' => $commentData['created_time'] ?? null,
            ]);
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
