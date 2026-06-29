<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Collection;

class RedirectSuggestionService
{
    /**
     * Suggest redirect targets for 404 log entries based on path similarity.
     * Uses similar_text() and levenshtein() to find the best match.
     *
     * @param  Collection  $existingPaths  - collection of known valid paths (from seo_metas canonical_url or static_urls)
     */
    public function suggestForLogs(Collection $logs404, Collection $existingPaths): Collection
    {
        return $logs404->map(function ($log) use ($existingPaths) {
            $suggestion = $this->findBestMatch($log->path, $existingPaths);

            return [
                'log_id' => $log->id,
                'path' => $log->path,
                'hit_count' => $log->hit_count,
                'suggested_target' => $suggestion['path'],
                'similarity' => $suggestion['similarity'],
                'confidence' => $suggestion['confidence'],
            ];
        });
    }

    private function findBestMatch(string $sourcePath, Collection $candidates): array
    {
        if ($candidates->isEmpty()) {
            return ['path' => '/', 'similarity' => 0, 'confidence' => 'low'];
        }

        $sourceParts = explode('/', trim($sourcePath, '/'));
        $sourceSlug = end($sourceParts);

        $bestScore = -1;
        $bestPath = '/';

        foreach ($candidates as $candidate) {
            $candidateParts = explode('/', trim($candidate, '/'));
            $candidateSlug = end($candidateParts);

            // Try similar_text on the full path
            similar_text($sourcePath, $candidate, $pathPercent);

            // Also try similar_text on just the slug (last segment)
            similar_text($sourceSlug, $candidateSlug, $slugPercent);

            // Weight: 60% full path + 40% slug similarity
            $score = ($pathPercent * 0.6) + ($slugPercent * 0.4);

            // Boost if same directory structure
            if (count($sourceParts) > 1 && isset($candidateParts[0]) && $sourceParts[0] === $candidateParts[0]) {
                $score += 10;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPath = $candidate;
            }
        }

        $confidence = match (true) {
            $bestScore >= 70 => 'high',
            $bestScore >= 40 => 'medium',
            default => 'low',
        };

        return ['path' => $bestPath, 'similarity' => round($bestScore, 1), 'confidence' => $confidence];
    }
}
