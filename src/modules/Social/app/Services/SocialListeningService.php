<?php

namespace Modules\Social\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Social\Events\NewMentionReceived;
use Modules\Social\Models\Mention;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Models\SocialListeningKeyword;

class SocialListeningService
{
    /**
     * Scan a specific keyword across all its configured networks
     */
    public function scanKeyword(SocialListeningKeyword $keyword): array
    {
        $results = [
            'keyword' => $keyword->name,
            'mentions_found' => 0,
            'networks_scanned' => [],
            'errors' => [],
        ];

        // Get active social accounts for this account
        $socialAccounts = SocialAccount::where('account_id', $keyword->account_id)
            ->where('status', 1)
            ->get();

        foreach ($keyword->networks as $network) {
            $account = $socialAccounts->firstWhere('network', $network);

            if (! $account) {
                $results['errors'][] = "No active account found for {$network}";

                continue;
            }

            try {
                $mentions = match ($network) {
                    'facebook' => $this->searchFacebook($keyword, $account),
                    'instagram' => $this->searchInstagram($keyword, $account),
                    'twitter' => $this->searchTwitter($keyword, $account),
                    'linkedin' => $this->searchLinkedIn($keyword, $account),
                    default => collect(),
                };

                $results['mentions_found'] += $mentions->count();
                $results['networks_scanned'][] = $network;

                // Store mentions
                foreach ($mentions as $mentionData) {
                    $this->storeMention($keyword, $account, $mentionData);
                }
            } catch (\Exception $e) {
                $results['errors'][] = "{$network}: {$e->getMessage()}";
                Log::error("Social listening scan failed for {$network}", [
                    'keyword' => $keyword->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update keyword scan timestamp and count
        $keyword->markAsScanned();

        return $results;
    }

    /**
     * Search Facebook for keyword mentions
     */
    protected function searchFacebook(SocialListeningKeyword $keyword, SocialAccount $account): Collection
    {
        $token = decrypt($account->access_token);
        $searchQuery = $keyword->getSearchQuery();

        // Facebook Graph API search
        $response = Http::get('https://graph.facebook.com/v21.0/search', [
            'q' => $searchQuery,
            'type' => 'post',
            'access_token' => $token,
            'fields' => 'id,message,from,created_time,permalink_url,full_picture',
            'limit' => 50,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Facebook API error: '.$response->json('error.message', 'Unknown error'));
        }

        $data = $response->json('data', []);

        return collect($data)->map(function ($item) {
            return [
                'external_id' => $item['id'],
                'author_id' => $item['from']['id'] ?? null,
                'author_name' => $item['from']['name'] ?? 'Unknown',
                'content' => $item['message'] ?? '',
                'external_url' => $item['permalink_url'] ?? null,
                'media_url' => $item['full_picture'] ?? null,
                'created_at' => $item['created_time'] ?? now(),
            ];
        });
    }

    /**
     * Search Instagram for keyword mentions
     */
    protected function searchInstagram(SocialListeningKeyword $keyword, SocialAccount $account): Collection
    {
        $token = decrypt($account->access_token);
        $searchQuery = $keyword->getSearchQuery();

        // Instagram Graph API - search hashtags or mentions
        if ($keyword->type === 'hashtag') {
            return $this->searchInstagramHashtag($searchQuery, $token);
        }

        // For mentions and keywords, search in recent media captions
        return $this->searchInstagramRecentMedia($searchQuery, $token, $account);
    }

    /**
     * Search Instagram hashtag
     */
    protected function searchInstagramHashtag(string $hashtag, string $token): Collection
    {
        $tag = str_replace('#', '', $hashtag);

        $response = Http::get('https://graph.facebook.com/v21.0/ig_hashtag_search', [
            'user_id' => 'me',
            'q' => $tag,
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            return collect();
        }

        $hashtagId = $response->json('data.0.id');

        if (! $hashtagId) {
            return collect();
        }

        // Get recent media for this hashtag
        $mediaResponse = Http::get("https://graph.facebook.com/v21.0/{$hashtagId}/recent_media", [
            'user_id' => 'me',
            'fields' => 'id,caption,media_url,permalink,timestamp,username',
            'limit' => 50,
            'access_token' => $token,
        ]);

        $data = $mediaResponse->json('data', []);

        return collect($data)->map(function ($item) {
            return [
                'external_id' => $item['id'],
                'author_name' => $item['username'] ?? 'Unknown',
                'content' => $item['caption'] ?? '',
                'external_url' => $item['permalink'] ?? null,
                'media_url' => $item['media_url'] ?? null,
                'created_at' => $item['timestamp'] ?? now(),
            ];
        });
    }

    /**
     * Search Instagram recent media
     */
    protected function searchInstagramRecentMedia(string $query, string $token, SocialAccount $account): Collection
    {
        $response = Http::get("https://graph.facebook.com/v21.0/{$account->external_id}/media", [
            'fields' => 'id,caption,media_url,permalink,timestamp,username',
            'limit' => 100,
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            return collect();
        }

        $data = $response->json('data', []);

        // Filter by keyword in caption
        return collect($data)
            ->filter(function ($item) use ($query) {
                $caption = $item['caption'] ?? '';

                return stripos($caption, $query) !== false;
            })
            ->map(function ($item) {
                return [
                    'external_id' => $item['id'],
                    'author_name' => $item['username'] ?? 'Unknown',
                    'content' => $item['caption'] ?? '',
                    'external_url' => $item['permalink'] ?? null,
                    'media_url' => $item['media_url'] ?? null,
                    'created_at' => $item['timestamp'] ?? now(),
                ];
            });
    }

    /**
     * Search Twitter for keyword mentions
     */
    protected function searchTwitter(SocialListeningKeyword $keyword, SocialAccount $account): Collection
    {
        $token = decrypt($account->access_token);
        $searchQuery = $keyword->getSearchQuery();

        // Twitter API v2 search
        $response = Http::withToken($token)->get('https://api.twitter.com/2/tweets/search/recent', [
            'query' => $searchQuery,
            'max_results' => 50,
            'tweet.fields' => 'created_at,author_id,text,entities',
            'expansions' => 'author_id,attachments.media_keys',
            'user.fields' => 'username,name,profile_image_url',
            'media.fields' => 'url,preview_image_url',
        ]);

        if (! $response->successful()) {
            throw new \Exception('Twitter API error: '.$response->json('errors.0.message', 'Unknown error'));
        }

        $tweets = $response->json('data', []);
        $users = collect($response->json('includes.users', []))->keyBy('id');
        $media = collect($response->json('includes.media', []))->keyBy('media_key');

        return collect($tweets)->map(function ($tweet) use ($users, $media) {
            $author = $users->get($tweet['author_id']);
            $mediaKey = $tweet['attachments']['media_keys'][0] ?? null;
            $mediaItem = $mediaKey ? $media->get($mediaKey) : null;

            return [
                'external_id' => $tweet['id'],
                'author_id' => $tweet['author_id'],
                'author_username' => $author['username'] ?? 'unknown',
                'author_name' => $author['name'] ?? 'Unknown',
                'author_avatar' => $author['profile_image_url'] ?? null,
                'content' => $tweet['text'],
                'external_url' => "https://twitter.com/{$author['username']}/status/{$tweet['id']}",
                'media_url' => $mediaItem['url'] ?? $mediaItem['preview_image_url'] ?? null,
                'created_at' => $tweet['created_at'] ?? now(),
            ];
        });
    }

    /**
     * Search LinkedIn for keyword mentions
     */
    protected function searchLinkedIn(SocialListeningKeyword $keyword, SocialAccount $account): Collection
    {
        // LinkedIn doesn't have a public search API for posts
        // This would require LinkedIn Marketing API or scraping
        // For now, return empty collection
        Log::info('LinkedIn social listening not yet implemented', [
            'keyword' => $keyword->name,
        ]);

        return collect();
    }

    /**
     * Store a mention in the database
     */
    protected function storeMention(
        SocialListeningKeyword $keyword,
        SocialAccount $socialAccount,
        array $mentionData
    ): ?Mention {
        // Check if mention already exists
        $exists = Mention::where('external_id', $mentionData['external_id'])
            ->where('social_account_id', $socialAccount->id)
            ->exists();

        if ($exists) {
            return null;
        }

        // Analyze sentiment
        $sentiment = $this->analyzeSentiment($mentionData['content']);

        // Check sentiment filter
        if ($keyword->sentiment_filter !== 'all' && $sentiment !== $keyword->sentiment_filter) {
            return null;
        }

        // Create mention
        $mention = Mention::create([
            'account_id' => $keyword->account_id,
            'social_account_id' => $socialAccount->id,
            'listening_keyword_id' => $keyword->id,
            'type' => 'mention', // All social listening results are mentions
            'external_id' => $mentionData['external_id'],
            'author_id' => $mentionData['author_id'] ?? null,
            'author_username' => $mentionData['author_username'] ?? null,
            'author_name' => $mentionData['author_name'],
            'author_avatar' => $mentionData['author_avatar'] ?? null,
            'content' => $mentionData['content'],
            'media_url' => $mentionData['media_url'] ?? null,
            'external_url' => $mentionData['external_url'] ?? null,
            'sentiment' => $sentiment,
            'is_read' => false,
            'is_archived' => false,
        ]);

        // Increment keyword mentions count
        $keyword->incrementMentionsCount();

        // Send notification if enabled
        if ($keyword->notify_on_match) {
            event(new NewMentionReceived($mention));
        }

        return $mention;
    }

    /**
     * Analyze sentiment of text
     */
    protected function analyzeSentiment(string $text): string
    {
        // Simple sentiment analysis based on keywords
        // In production, use a proper sentiment analysis API like:
        // - Google Cloud Natural Language API
        // - AWS Comprehend
        // - Azure Text Analytics
        // - OpenAI GPT for sentiment analysis

        $positiveWords = ['great', 'love', 'awesome', 'excellent', 'amazing', 'good', 'wonderful', 'fantastic', 'perfect', 'best'];
        $negativeWords = ['bad', 'hate', 'terrible', 'awful', 'horrible', 'worst', 'poor', 'disappointing', 'useless', 'suck'];

        $text = strtolower($text);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (stripos($text, $word) !== false) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (stripos($text, $word) !== false) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Scan all active keywords
     */
    public function scanAllKeywords(int $accountId): array
    {
        $keywords = SocialListeningKeyword::where('account_id', $accountId)
            ->needsScanning()
            ->get();

        $results = [
            'keywords_scanned' => 0,
            'total_mentions' => 0,
            'errors' => [],
        ];

        foreach ($keywords as $keyword) {
            $scanResult = $this->scanKeyword($keyword);
            $results['keywords_scanned']++;
            $results['total_mentions'] += $scanResult['mentions_found'];
            $results['errors'] = array_merge($results['errors'], $scanResult['errors']);
        }

        return $results;
    }

    /**
     * Get listening stats for dashboard
     */
    public function getListeningStats(int $accountId): array
    {
        $keywords = SocialListeningKeyword::where('account_id', $accountId)->get();

        $mentions = Mention::where('account_id', $accountId)
            ->whereNotNull('listening_keyword_id')
            ->get();

        $recentMentions = $mentions->where('created_at', '>=', now()->subDays(7));

        return [
            'total_keywords' => $keywords->count(),
            'active_keywords' => $keywords->where('is_active', true)->count(),
            'total_mentions' => $mentions->count(),
            'recent_mentions' => $recentMentions->count(),
            'sentiment_distribution' => [
                'positive' => $mentions->where('sentiment', 'positive')->count(),
                'negative' => $mentions->where('sentiment', 'negative')->count(),
                'neutral' => $mentions->where('sentiment', 'neutral')->count(),
            ],
            'mentions_by_network' => $mentions->groupBy('socialAccount.network')->map->count(),
            'top_keywords' => $keywords->sortByDesc('mentions_count')->take(5)->values(),
        ];
    }
}
