<?php

namespace Modules\Supplier\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;

class CategoryTreeHelper
{
    private const CACHE_TTL = 1800;

    /**
     * Get the complete category tree: Sport > Category
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getFullTree(bool $activeOnly = true, bool $withCounts = false): array
    {
        $cacheKey = sprintf(
            'supplier:category_tree:%s:%s',
            $activeOnly ? 'active' : 'all',
            $withCounts ? 'counts' : 'plain'
        );

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($activeOnly, $withCounts): array {
            $categoryConstraint = function ($query) use ($activeOnly, $withCounts): void {
                if ($activeOnly) {
                    $query->where('available', true);
                }

                if ($withCounts) {
                    $query->withCount('products');
                }
            };

            $query = Sport::with(['categories' => $categoryConstraint]);

            if ($activeOnly) {
                $query->where('available', true)
                    ->whereHas('categories', fn ($q) => $q->where('available', true));
            }

            return $query->get()->map(function (Sport $sport) use ($withCounts): array {
                $node = [
                    'id' => $sport->id,
                    'erp_id' => $sport->erp_id,
                    'type' => 'sport',
                    'name' => $sport->name,
                    'short_name' => $sport->short_name,
                    'available' => $sport->available,
                ];

                if ($withCounts) {
                    $node['categories_count'] = $sport->categories->count();
                }

                $node['children'] = $sport->categories->map(function (Category $category) use ($withCounts): array {
                    $child = [
                        'id' => $category->id,
                        'erp_id' => $category->erp_id,
                        'type' => 'category',
                        'name' => $category->name,
                        'short_name' => $category->short_name,
                        'available' => $category->available,
                    ];

                    if ($withCounts) {
                        $child['products_count'] = (int) $category->products_count;
                    }

                    return $child;
                })->values()->toArray();

                return $node;
            })->toArray();
        });
    }

    /**
     * Search across sports and categories
     *
     * @return array{sports: Collection, categories: Collection}
     */
    public static function search(string $term): array
    {
        return [
            'sports' => Sport::search($term)->active()->limit(10)->get(),
            'categories' => Category::search($term)->active()->limit(10)->get(),
        ];
    }

    /**
     * Get flat list of all categories with sport path
     */
    public static function getFlatList(bool $activeOnly = true): Collection
    {
        $cacheKey = 'supplier:category_tree:flat:'.($activeOnly ? 'active' : 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($activeOnly): Collection {
            $query = Category::with('sport');

            if ($activeOnly) {
                $query->where('available', true);
            }

            return $query->get()->map(fn (Category $category): array => [
                'id' => $category->id,
                'erp_id' => $category->erp_id,
                'type' => 'category',
                'name' => $category->name,
                'full_path' => $category->sport
                    ? $category->sport->name.' > '.$category->name
                    : $category->name,
            ]);
        });
    }

    /**
     * Invalidate every cached representation of the category tree.
     * Call this whenever a Sport or Category is created, updated or deleted.
     */
    public static function flushCache(): void
    {
        foreach (['active', 'all'] as $scope) {
            Cache::forget("supplier:category_tree:{$scope}:counts");
            Cache::forget("supplier:category_tree:{$scope}:plain");
            Cache::forget("supplier:category_tree:flat:{$scope}");
        }
    }
}
