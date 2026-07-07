<?php

namespace Modules\Blog\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Blog\Events\TranslationCompleted;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Notifications\TranslationCompletedNotification;
use Modules\Blog\Services\BlogTranslationService;

class TranslateBlogPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 15;

    public function __construct(
        private readonly int $postId,
        private readonly ?string $targetLocale = null,
        private readonly ?string $sourceLocale = null,
        private readonly ?array $onlyFields = null,
        private readonly ?int $userId = null,
    ) {}

    public function handle(BlogTranslationService $service): void
    {
        $post = BlogPost::query()->find($this->postId);

        if (! $post) {
            Log::warning('TranslateBlogPostJob: post not found', ['post_id' => $this->postId]);

            return;
        }

        if ($this->targetLocale) {
            $translation = $service->translatePost(
                $post,
                $this->targetLocale,
                $this->sourceLocale,
                $this->onlyFields,
            );

            $results = [$this->targetLocale => 'ok'];
            $translations = [$this->targetLocale => $translation];
        } else {
            $result = $service->translateAllLocales($post, $this->sourceLocale);
            $results = $result['results'];
            $translations = $result['translations'];
        }

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new TranslationCompletedNotification($post, $results));
            }
        }

        // Broadcast event for real-time UI update
        event(new TranslationCompleted($post, $results, $translations));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TranslateBlogPostJob failed', [
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
        ]);
    }
}
