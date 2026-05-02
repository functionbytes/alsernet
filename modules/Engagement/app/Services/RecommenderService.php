<?php

namespace Modules\Engagement\Services;

use Modules\Engagement\Models\CatalogProduct;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\RecommendationProfile;

class RecommenderService
{
    public function recommend(string $sessionToken, ?int $customerId, int $limit = 5, ?int $inboxId = null): array
    {
        $profile = $customerId
            ? RecommendationProfile::query()->where('customer_id', $customerId)->first()
            : null;

        $excludeIds = $this->getRecentProductIds($sessionToken);
        $favCategories = $this->extractFavoriteCategories($profile);

        return $this->buildSuggestions($inboxId, $favCategories, $excludeIds, $limit);
    }

    public function updateProfile(string $sessionToken, ?int $customerId): void
    {
        if (! $customerId) {
            return;
        }

        $productViews = Event::query()
            ->forSession($sessionToken)
            ->ofType('product_view')
            ->recent(30 * 24)
            ->get(['properties']);

        $profile = RecommendationProfile::query()->firstOrCreate(
            ['customer_id' => $customerId],
            ['viewed_products' => [], 'categories' => [], 'cart_history' => []],
        );

        foreach ($productViews as $event) {
            $profile->recordProductView($event->properties ?? []);
        }
    }

    private function getRecentProductIds(string $sessionToken): array
    {
        return Event::query()
            ->forSession($sessionToken)
            ->ofType('product_view')
            ->recent(2)
            ->get(['properties'])
            ->map(fn ($e) => $e->properties['productId'] ?? $e->properties['id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    private function extractFavoriteCategories(?RecommendationProfile $profile): array
    {
        if (! $profile) {
            return [];
        }

        return collect($profile->categories ?? [])
            ->sortByDesc('count')
            ->take(3)
            ->pluck('name')
            ->filter()
            ->values()
            ->toArray();
    }

    private function buildSuggestions(?int $inboxId, array $favCategories, array $excludeIds, int $limit): array
    {
        $query = CatalogProduct::query()->active();

        if ($inboxId) {
            $query->forInbox($inboxId);
        }

        if (! empty($excludeIds)) {
            $query->whereNotIn('platform_product_id', $excludeIds);
        }

        if (! empty($favCategories)) {
            $query->whereIn('category', $favCategories);
        }

        $products = $query->inRandomOrder()->limit($limit)->get();

        if ($products->count() < $limit && ! empty($favCategories)) {
            $extra = CatalogProduct::query()
                ->active()
                ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
                ->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('platform_product_id', $excludeIds))
                ->whereNotIn('id', $products->pluck('id'))
                ->inRandomOrder()
                ->limit($limit - $products->count())
                ->get();

            $products = $products->concat($extra);
        }

        return $products->map(fn (CatalogProduct $p) => $p->toRecommendation())->values()->toArray();
    }
}
