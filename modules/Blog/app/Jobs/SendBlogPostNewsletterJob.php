<?php

namespace Modules\Blog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Models\BlogPost;

class SendBlogPostNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [60, 120, 300];

    public function __construct(
        public readonly int $postId
    ) {}

    public function handle(): void
    {
        $post = BlogPost::find($this->postId);

        if (! $post || $post->status !== PostStatus::Published) {
            return;
        }

        if (! $post->send_newsletter) {
            return;
        }

        $this->sendViaMailrelay($post);
    }

    private function sendViaMailrelay(BlogPost $post): void
    {
        $apiKey = config('services.mailrelay.api_key');
        $apiHost = config('services.mailrelay.host');

        if (! $apiKey || ! $apiHost) {
            Log::warning('Mailrelay not configured, skipping newsletter for post', ['post_id' => $post->id]);

            return;
        }

        $html = view('blog::emails.newsletter-post', ['post' => $post])->render();

        $payload = [
            'subject' => $post->title,
            'from_name' => config('mail.from.name'),
            'from_email' => config('mail.from.address'),
            'html' => $html,
            'send_now' => true,
        ];

        $groupId = config('services.mailrelay.newsletter_group_id');

        if ($groupId) {
            $payload['groups_id'] = [(int) $groupId];
        }

        $response = Http::withHeaders([
            'X-Auth-Token' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://{$apiHost}/api/v1/campaigns", $payload);

        if (! $response->successful()) {
            Log::error('Failed to send blog newsletter via Mailrelay', [
                'post_id' => $post->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Mailrelay API error: '.$response->body());
        }

        Log::info('Blog newsletter sent via Mailrelay', [
            'post_id' => $post->id,
            'subject' => $post->title,
        ]);

        $post->updateQuietly(['newsletter_sent_at' => now()]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendBlogPostNewsletterJob failed permanently', [
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
        ]);
    }
}
