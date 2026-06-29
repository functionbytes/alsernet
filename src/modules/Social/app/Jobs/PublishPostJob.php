<?php

namespace Modules\Social\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Models\Post;
use Modules\Social\Services\Publishers\FacebookPublisher;
use Modules\Social\Services\Publishers\InstagramPublisher;
use Modules\Social\Services\Publishers\LinkedInPublisher;
use Modules\Social\Services\Publishers\TwitterPublisher;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    public function __construct(
        public Post $post
    ) {}

    public function handle(): void
    {
        $this->post->refresh();

        if (! in_array($this->post->status, [PostStatus::DRAFT, PostStatus::FAILED, PostStatus::SCHEDULED])) {
            Log::info("Post {$this->post->id} is not in publishable state. Current status: {$this->post->status->value}");

            return;
        }

        try {
            $this->post->update(['status' => PostStatus::PUBLISHING]);

            $publisher = $this->getPublisher();

            if (! $publisher) {
                throw new Exception("No publisher found for network: {$this->post->socialAccount->network->value}");
            }

            $result = $publisher->publish($this->post);

            $this->post->update([
                'status' => PostStatus::PUBLISHED,
                'published_at' => now(),
                'external_id' => $result['id'] ?? null,
                'external_url' => $result['url'] ?? null,
            ]);

            $this->post->socialAccount->update([
                'last_sync_at' => now(),
                'last_error' => null,
            ]);

            Log::info("Successfully published post {$this->post->id} to {$this->post->socialAccount->network->value}");
        } catch (Exception $e) {
            Log::error("Failed to publish post {$this->post->id}: {$e->getMessage()}", [
                'exception' => $e,
                'post_id' => $this->post->id,
                'network' => $this->post->socialAccount->network->value,
            ]);

            $this->post->update([
                'status' => PostStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $this->post->socialAccount->update([
                'last_error' => $e->getMessage(),
            ]);

            if ($this->isTokenExpiredError($e)) {
                $this->post->socialAccount->update([
                    'status' => 2,
                ]);
            }

            throw $e;
        }
    }

    protected function getPublisher()
    {
        return match ($this->post->socialAccount->network->value) {
            'facebook' => app(FacebookPublisher::class),
            'instagram' => app(InstagramPublisher::class),
            'twitter' => app(TwitterPublisher::class),
            'linkedin' => app(LinkedInPublisher::class),
            default => null,
        };
    }

    protected function isTokenExpiredError(Exception $e): bool
    {
        $message = $e->getMessage();
        $code = $e->getCode();

        if ($code == 190) {
            return true;
        }

        if ($code == 401) {
            return true;
        }

        $expirationPhrases = [
            'token expired',
            'token has expired',
            'access token',
            'unauthorized',
            'invalid token',
        ];

        foreach ($expirationPhrases as $phrase) {
            if (stripos($message, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }

    public function failed(Exception $exception): void
    {
        Log::error("PublishPostJob permanently failed for post {$this->post->id}", [
            'exception' => $exception,
            'post_id' => $this->post->id,
        ]);

        $this->post->update([
            'status' => PostStatus::FAILED,
            'error_message' => "Failed after {$this->tries} attempts: {$exception->getMessage()}",
        ]);
    }
}
