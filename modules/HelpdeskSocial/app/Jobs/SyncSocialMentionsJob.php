<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialListeningKeyword;
use Modules\HelpdeskSocial\Models\SocialMention;
use Modules\HelpdeskSocial\Services\SentimentAnalysisService;
use Modules\HelpdeskSocial\Services\SocialListeningService;

class SyncSocialMentionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct(
        public readonly ?string $platform = null,
    ) {
        $this->onQueue(config('helpdesksocial.queues.processing', 'helpdesk-social-processing'));
    }

    public function handle(
        SocialListeningService $listeningService,
        SentimentAnalysisService $sentimentService,
    ): void {
        $keywords = SocialListeningKeyword::active()
            ->when($this->platform, function ($query) {
                $query->whereJsonContains('platforms', $this->platform);
            })
            ->get();

        if ($keywords->isEmpty()) {
            Log::info('SyncSocialMentionsJob: No active keywords found', [
                'platform' => $this->platform,
            ]);

            return;
        }

        foreach ($keywords as $keyword) {
            try {
                $this->syncMentionsForKeyword($keyword, $listeningService, $sentimentService);
            } catch (\Throwable $e) {
                Log::error('SyncSocialMentionsJob: Failed to sync keyword', [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function syncMentionsForKeyword(
        SocialListeningKeyword $keyword,
        SocialListeningService $listeningService,
        SentimentAnalysisService $sentimentService,
    ): void {
        $platforms = $keyword->platforms ?? ['facebook', 'instagram'];

        foreach ($platforms as $platform) {
            $mentions = $listeningService->searchMentions($keyword->keyword, $platform);

            foreach ($mentions as $mentionData) {
                $externalId = $mentionData['external_id'] ?? null;

                if (! $externalId) {
                    continue;
                }

                $existing = SocialMention::where('platform', $platform)
                    ->where('external_id', $externalId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'engagement_count' => $mentionData['engagement_count'] ?? $existing->engagement_count,
                        'updated_at' => now(),
                    ]);

                    continue;
                }

                $mention = SocialMention::create([
                    'platform' => $platform,
                    'external_id' => $externalId,
                    'author_name' => $mentionData['author_name'] ?? 'Usuario',
                    'author_username' => $mentionData['author_username'] ?? null,
                    'body' => $mentionData['body'] ?? '',
                    'url' => $mentionData['url'] ?? null,
                    'matched_keywords' => [$keyword->keyword],
                    'engagement_count' => $mentionData['engagement_count'] ?? 0,
                    'status' => 'new',
                    'discovered_at' => now(),
                    'posted_at' => $mentionData['posted_at'] ?? now(),
                ]);

                // Analyze sentiment for the mention
                try {
                    $result = $sentimentService->analyzeText($mention->body);
                    $mention->update([
                        'sentiment' => $result['label'] ?? 'neutral',
                        'sentiment_score' => $result['score'] ?? 0.5,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('SyncSocialMentionsJob: Sentiment analysis failed for mention', [
                        'mention_id' => $mention->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncSocialMentionsJob failed permanently', [
            'platform' => $this->platform,
            'error' => $exception->getMessage(),
        ]);
    }
}
