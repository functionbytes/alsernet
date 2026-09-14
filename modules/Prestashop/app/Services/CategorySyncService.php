<?php

namespace Modules\Prestashop\Services;

use App\Models\PrestaShopCategoryMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Prestashop\Entities\Category as PrestaShopCategory;

/**
 * CategorySyncService
 *
 * Handles bidirectional synchronization between Laravel and PrestaShop categories
 * with conflict detection and resolution strategies
 */
class CategorySyncService
{
    // Sync status constants
    protected const SYNC_STATUS_PENDING = 'pending';

    protected const SYNC_STATUS_IN_PROGRESS = 'in_progress';

    protected const SYNC_STATUS_SUCCESS = 'success';

    protected const SYNC_STATUS_FAILED = 'failed';

    protected const SYNC_STATUS_CONFLICT = 'conflict';

    // Conflict resolution strategies
    protected const CONFLICT_LARAVEL_WINS = 'laravel_wins';

    protected const CONFLICT_PRESTASHOP_WINS = 'prestashop_wins';

    protected const CONFLICT_MERGE = 'merge';

    protected const CONFLICT_MANUAL = 'manual';

    protected const CONFLICT_SKIP = 'skip';

    // Sync directions
    protected const SYNC_DIRECTION_LARAVEL_TO_PS = 'laravel_to_ps';

    protected const SYNC_DIRECTION_PS_TO_LARAVEL = 'ps_to_laravel';

    protected const SYNC_DIRECTION_MANUAL = 'manual';

    // Retry configuration
    protected const MAX_RETRY_ATTEMPTS = 3;

    protected const RETRY_DELAY_SECONDS = 5;

    /**
     * Synchronize Laravel category to PrestaShop
     *
     * @param  Category  $category  The Laravel category to sync
     * @param  bool  $recursive  Whether to sync children recursively
     * @return bool Success status
     */
    public function syncToPrestaShop(Category $category, bool $recursive = false): bool
    {
        try {
            DB::beginTransaction();

            // Validate category
            if (! $this->validateCategoryForSync($category)) {
                Log::warning("Category {$category->id} failed validation for sync", [
                    'category_id' => $category->id,
                    'title' => $category->title,
                ]);

                return false;
            }

            // Map Laravel data to PrestaShop format
            $prestashopData = $this->mapLaravelToPrestaShop($category);

            // Create or update PrestaShop category
            if ($category->prestashop_id) {
                // Update existing PrestaShop category
                $prestashopCategory = PrestaShopCategory::find($category->prestashop_id);
                if ($prestashopCategory) {
                    $prestashopCategory->update($prestashopData);
                } else {
                    // PrestaShop category was deleted, create new one
                    $prestashopCategory = PrestaShopCategory::create($prestashopData);
                    $category->update(['prestashop_id' => $prestashopCategory->id_category]);
                }
            } else {
                // Create new PrestaShop category
                $prestashopCategory = PrestaShopCategory::create($prestashopData);
                $category->update(['prestashop_id' => $prestashopCategory->id_category]);
            }

            // Update or create mapping
            $mapping = $this->upsertMapping(
                $category,
                $prestashopCategory,
                self::SYNC_DIRECTION_LARAVEL_TO_PS
            );

            $mapping->markAsSuccess();

            // Update category's last_synced_at
            $category->update(['last_synced_at' => now()]);

            DB::commit();

            // Log success
            Log::info('Category synced to PrestaShop', [
                'category_id' => $category->id,
                'prestashop_id' => $prestashopCategory->id_category,
            ]);

            // Sync children recursively if requested
            if ($recursive && $category->children()->count() > 0) {
                foreach ($category->children as $child) {
                    $this->syncToPrestaShop($child, true);
                }
            }

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->markSyncFailed($category, $e->getMessage());
            Log::error('Failed to sync category to PrestaShop', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Synchronize PrestaShop category to Laravel
     *
     * @param  int  $prestashopCategoryId  PrestaShop category ID
     * @return bool Success status
     */
    public function syncFromPrestaShop(int $prestashopCategoryId): bool
    {
        try {
            DB::beginTransaction();

            // Get PrestaShop category
            $prestashopCategory = PrestaShopCategory::find($prestashopCategoryId);
            if (! $prestashopCategory) {
                Log::warning('PrestaShop category not found', [
                    'prestashop_id' => $prestashopCategoryId,
                ]);

                return false;
            }

            // Check if category already exists in Laravel
            $category = Category::where('prestashop_id', $prestashopCategoryId)->first();

            // Map PrestaShop data to Laravel format
            $laravelData = $this->mapPrestaShopToLaravel($prestashopCategory);

            if ($category) {
                // Update existing Laravel category
                $category->update($laravelData);
            } else {
                // Create new Laravel category
                $category = Category::create($laravelData);
            }

            // Sync hierarchy: find parent by id_parent
            if ($prestashopCategory->id_parent && $prestashopCategory->id_parent > 0) {
                $parentCategory = Category::where('prestashop_id', $prestashopCategory->id_parent)->first();
                if (! $parentCategory) {
                    // Parent doesn't exist in Laravel, import it first (recursive)
                    $this->syncFromPrestaShop($prestashopCategory->id_parent);
                    $parentCategory = Category::where('prestashop_id', $prestashopCategory->id_parent)->first();
                }
                if ($parentCategory) {
                    $category->update(['parent_id' => $parentCategory->id]);
                }
            }

            // Update or create mapping
            $mapping = $this->upsertMapping(
                $category,
                $prestashopCategory,
                self::SYNC_DIRECTION_PS_TO_LARAVEL
            );

            $mapping->markAsSuccess();

            // Update category's last_synced_at
            $category->update(['last_synced_at' => now()]);

            DB::commit();

            // Log success
            Log::info('Category synced from PrestaShop', [
                'category_id' => $category->id,
                'prestashop_id' => $prestashopCategoryId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to sync category from PrestaShop', [
                'prestashop_id' => $prestashopCategoryId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Detect conflicts between Laravel and PrestaShop categories
     *
     * @return array Array of mappings with conflicts
     */
    public function detectConflicts(): array
    {
        $conflicts = [];

        $mappings = PrestaShopCategoryMapping::with(['category', 'prestashopCategory'])
            ->whereNotNull('last_synced_at')
            ->get();

        foreach ($mappings as $mapping) {
            if (! $mapping->category || ! $mapping->prestashopCategory) {
                continue;
            }

            $category = $mapping->category;
            $prestashopCategory = $mapping->prestashopCategory;

            // Check if both were modified after last sync
            $categoryModified = $category->updated_at > $mapping->last_synced_at;
            $prestashopModified = $prestashopCategory->date_upd > $mapping->last_synced_at;

            if ($categoryModified && $prestashopModified) {
                // CONFLICT DETECTED
                $conflictData = $this->compareFields($category, $prestashopCategory);

                $mapping->markAsConflict($conflictData);

                $conflicts[] = $mapping;

                Log::warning('Conflict detected', [
                    'mapping_id' => $mapping->id,
                    'category_id' => $category->id,
                    'prestashop_id' => $prestashopCategory->id_category,
                    'conflicts' => $conflictData,
                ]);
            }
        }

        return $conflicts;
    }

    /**
     * Resolve conflict with specified strategy
     *
     * @param  PrestaShopCategoryMapping  $mapping  The mapping with conflict
     * @param  string  $strategy  Resolution strategy
     * @return bool Success status
     */
    public function resolveConflict(PrestaShopCategoryMapping $mapping, string $strategy): bool
    {
        try {
            switch ($strategy) {
                case self::CONFLICT_LARAVEL_WINS:
                    // Laravel data overwrites PrestaShop
                    $this->syncToPrestaShop($mapping->category);
                    $mapping->resolveConflict($strategy);
                    break;

                case self::CONFLICT_PRESTASHOP_WINS:
                    // PrestaShop data overwrites Laravel
                    $this->syncFromPrestaShop($mapping->prestashop_category_id);
                    $mapping->resolveConflict($strategy);
                    break;

                case self::CONFLICT_MERGE:
                    // Intelligent merge
                    $this->mergeConflictedData($mapping->category, $mapping->prestashopCategory);
                    $mapping->resolveConflict($strategy);
                    break;

                case self::CONFLICT_SKIP:
                    // Do nothing, mark as skipped
                    $mapping->resolveConflict($strategy);
                    break;

                case self::CONFLICT_MANUAL:
                    // Keep in conflict state for manual resolution
                    return true;

                default:
                    throw new \InvalidArgumentException("Invalid resolution strategy: {$strategy}");
            }

            Log::info('Conflict resolved', [
                'mapping_id' => $mapping->id,
                'strategy' => $strategy,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to resolve conflict', [
                'mapping_id' => $mapping->id,
                'strategy' => $strategy,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Map Laravel category data to PrestaShop format
     */
    protected function mapLaravelToPrestaShop(Category $category): array
    {
        $data = [
            'name' => $category->title,
            'link_rewrite' => $category->slug ?? Str::slug($category->title),
            'description' => $category->meta_description ?? '',
            'meta_title' => $category->meta_title ?? $category->title,
            'meta_description' => $category->meta_description ?? '',
            'meta_keywords' => $category->meta_keywords ?? '',
            'active' => $category->active ? 1 : 0,
            'position' => $category->position,
            'date_upd' => now(),
        ];

        // Map parent
        if ($category->parent_id) {
            $parent = Category::find($category->parent_id);
            $data['id_parent'] = $parent->prestashop_id ?? 2; // 2 is root in PrestaShop
        } else {
            $data['id_parent'] = 2; // Root category
        }

        return $data;
    }

    /**
     * Map PrestaShop category data to Laravel format
     */
    protected function mapPrestaShopToLaravel(PrestaShopCategory $prestashopCategory): array
    {
        return [
            'title' => $prestashopCategory->name,
            'slug' => $prestashopCategory->link_rewrite,
            'meta_title' => $prestashopCategory->meta_title,
            'meta_description' => $prestashopCategory->meta_description,
            'meta_keywords' => $prestashopCategory->meta_keywords,
            'active' => (bool) $prestashopCategory->active,
            'position' => $prestashopCategory->position,
            'prestashop_id' => $prestashopCategory->id_category,
        ];
    }

    /**
     * Compare fields between Laravel and PrestaShop categories
     *
     * @return array Array of conflicting fields
     */
    protected function compareFields(Category $category, PrestaShopCategory $prestashopCategory): array
    {
        $conflicts = [];

        $comparisons = [
            'title' => [$category->title, $prestashopCategory->name],
            'slug' => [$category->slug, $prestashopCategory->link_rewrite],
            'meta_title' => [$category->meta_title, $prestashopCategory->meta_title],
            'meta_description' => [$category->meta_description, $prestashopCategory->meta_description],
            'active' => [$category->active, (bool) $prestashopCategory->active],
            'position' => [$category->position, $prestashopCategory->position],
        ];

        foreach ($comparisons as $field => $values) {
            if ($values[0] !== $values[1]) {
                $conflicts[$field] = [
                    'laravel' => $values[0],
                    'prestashop' => $values[1],
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Merge conflicted data intelligently
     */
    protected function mergeConflictedData(Category $category, PrestaShopCategory $prestashopCategory): void
    {
        // Merge strategy: Take title from Laravel, meta fields from PrestaShop
        $category->update([
            'meta_title' => $prestashopCategory->meta_title,
            'meta_description' => $prestashopCategory->meta_description,
            'meta_keywords' => $prestashopCategory->meta_keywords,
        ]);

        // Sync back to PrestaShop
        $this->syncToPrestaShop($category);
    }

    /**
     * Validate category for synchronization
     */
    protected function validateCategoryForSync(Category $category): bool
    {
        // Title must not be empty
        if (empty($category->title)) {
            return false;
        }

        // Check depth limit (PrestaShop typically supports up to 8 levels)
        if ($category->depth > 8) {
            return false;
        }

        return true;
    }

    /**
     * Create or update mapping record
     */
    protected function upsertMapping(
        Category $category,
        PrestaShopCategory $prestashopCategory,
        string $direction
    ): PrestaShopCategoryMapping {
        return PrestaShopCategoryMapping::updateOrCreate(
            [
                'category_id' => $category->id,
                'prestashop_category_id' => $prestashopCategory->id_category,
            ],
            [
                'sync_direction' => $direction,
                'sync_status' => self::SYNC_STATUS_SUCCESS,
                'last_synced_at' => now(),
                'sync_attempts' => 0,
                'last_sync_attempt_at' => now(),
            ]
        );
    }

    /**
     * Mark sync as failed
     */
    protected function markSyncFailed(Category $category, string $errorMessage): void
    {
        $mapping = PrestaShopCategoryMapping::where('category_id', $category->id)->first();
        if ($mapping) {
            $mapping->markAsFailed($errorMessage);
        }
    }
}
