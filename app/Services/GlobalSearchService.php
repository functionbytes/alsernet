<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GlobalSearchService
{
    private array $searchers = [];

    public function register(string $key, callable $searcher): void
    {
        $this->searchers[$key] = $searcher;
    }

    public function search(string $query, int $limit = 5): Collection
    {
        if (strlen(trim($query)) < 3) {
            return collect();
        }

        $results = collect();

        foreach ($this->searchers as $searcher) {
            try {
                $items = $searcher($query, $limit);
                $results = $results->merge($items);
            } catch (\Throwable) {
                // Module unavailable, skip silently
            }
        }

        return $results->sortByDesc('relevance')->take(20)->values();
    }
}
