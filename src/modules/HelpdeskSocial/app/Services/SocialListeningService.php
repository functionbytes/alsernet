<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialListeningKeyword;
use Modules\HelpdeskSocial\Models\SocialMention;

class SocialListeningService
{
    /**
     * Scan a comment against all active listening keywords and create mentions for matches.
     *
     * @return array<int, array<string, mixed>>
     */
    public function scanComment(SocialComment $comment): array
    {
        $matches = [];
        $keywords = SocialListeningKeyword::active()->get();

        foreach ($keywords as $keyword) {
            if (! $this->platformMatches($comment->platform, $keyword)) {
                continue;
            }

            if ($this->matchesKeyword($comment->body, $keyword)) {
                $sentiment = $comment->sentiment['label'] ?? 'neutral';

                if ($keyword->sentiment_filter && $keyword->sentiment_filter !== $sentiment) {
                    continue;
                }

                $mention = $this->createMention($comment, $keyword);
                $matches[] = [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'mention_id' => $mention?->id,
                ];
            }
        }

        return $matches;
    }

    /**
     * Scan arbitrary text (e.g., from external sources) against active keywords.
     *
     * @return array<int, array<string, mixed>>
     */
    public function scanText(string $text, string $platform, ?string $authorName = null, ?string $authorUsername = null, ?string $externalId = null, ?string $url = null): array
    {
        $matches = [];
        $keywords = SocialListeningKeyword::active()->get();

        foreach ($keywords as $keyword) {
            if (! $this->platformMatches($platform, $keyword)) {
                continue;
            }

            if ($this->matchesKeyword($text, $keyword)) {
                $mention = SocialMention::create([
                    'platform' => $platform,
                    'external_id' => $externalId,
                    'author_name' => $authorName,
                    'author_username' => $authorUsername,
                    'body' => $text,
                    'url' => $url,
                    'matched_keywords' => [$keyword->keyword],
                    'sentiment' => 'neutral',
                    'sentiment_score' => 0.5,
                    'status' => 'new',
                    'discovered_at' => now(),
                ]);

                $matches[] = [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'mention_id' => $mention->id,
                ];
            }
        }

        return $matches;
    }

    private function platformMatches(string $platform, SocialListeningKeyword $keyword): bool
    {
        $platforms = $keyword->platforms ?? [];

        if (empty($platforms)) {
            return true;
        }

        return in_array($platform, $platforms, true);
    }

    private function matchesKeyword(string $text, SocialListeningKeyword $keyword): bool
    {
        $matchType = $keyword->match_type ?? 'contains';
        $needle = $keyword->keyword;

        return match ($matchType) {
            'exact' => mb_strtolower(trim($text)) === mb_strtolower(trim($needle)),
            'contains' => str_contains(mb_strtolower($text), mb_strtolower($needle)),
            'regex' => (bool) preg_match($needle, $text),
            default => false,
        };
    }

    private function createMention(SocialComment $comment, SocialListeningKeyword $keyword): ?SocialMention
    {
        try {
            $sentiment = $comment->sentiment['label'] ?? 'neutral';
            $sentimentScore = $comment->sentiment['score'] ?? 0.5;

            return SocialMention::create([
                'platform' => $comment->platform,
                'external_id' => $comment->external_comment_id,
                'author_name' => $comment->author_name,
                'author_username' => $comment->author_username,
                'body' => $comment->body,
                'url' => $comment->metadata['url'] ?? null,
                'matched_keywords' => [$keyword->keyword],
                'sentiment' => $sentiment,
                'sentiment_score' => $sentimentScore,
                'status' => 'new',
                'discovered_at' => now(),
                'posted_at' => $comment->posted_at,
            ]);
        } catch (\Throwable $e) {
            Log::error('SocialListeningService: failed to create mention', [
                'comment_id' => $comment->id,
                'keyword_id' => $keyword->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
