<?php

namespace Modules\Page\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Page\Models\Page;

class PageService
{
    /**
     * Create a new page.
     *
     * @throws Exception
     */
    public function createPage(array $data): Page
    {
        try {
            DB::beginTransaction();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            }

            // Set user_id if authenticated
            if (Auth::check() && ! isset($data['user_id'])) {
                $data['user_id'] = Auth::id();
            }

            // Set published_at if status is published
            if ($data['status'] === Page::STATUS_PUBLISHED && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            // Clear publish_at if publishing immediately
            if ($data['status'] === Page::STATUS_PUBLISHED && isset($data['publish_at'])) {
                $data['publish_at'] = null;
            }

            // Clear unpublish_at if status is draft
            if ($data['status'] === Page::STATUS_DRAFT && isset($data['unpublish_at'])) {
                $data['unpublish_at'] = null;
            }

            // Create the page
            $page = Page::create($data);

            // Handle media if provided
            if (isset($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
                $this->handleMedia($page, $data['featured_image']);
            }

            DB::commit();

            return $page->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing page.
     *
     * @throws Exception
     */
    public function updatePage(Page $page, array $data): Page
    {
        try {
            DB::beginTransaction();

            // Update slug if title changed and slug not explicitly set
            if (isset($data['title']) && $data['title'] !== $page->title && empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title'], $page->id);
            }

            // Set published_at if changing to published status
            if (isset($data['status']) &&
                $data['status'] === Page::STATUS_PUBLISHED &&
                $page->status !== Page::STATUS_PUBLISHED &&
                empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            // Clear publish_at if publishing immediately
            if (isset($data['status']) && $data['status'] === Page::STATUS_PUBLISHED && $page->publish_at) {
                $data['publish_at'] = null;
            }

            // Clear unpublish_at if changing to draft
            if (isset($data['status']) && $data['status'] === Page::STATUS_DRAFT && $page->unpublish_at) {
                $data['unpublish_at'] = null;
            }

            // Update the page
            $page->update($data);

            // Handle media if provided
            if (isset($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
                $this->handleMedia($page, $data['featured_image']);
            }

            DB::commit();

            return $page->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a page.
     *
     * @throws Exception
     */
    public function deletePage(Page $page): bool
    {
        try {
            DB::beginTransaction();

            // Clear all media
            $page->clearMediaCollection('featured');
            $page->clearMediaCollection('gallery');

            // Soft delete the page
            $deleted = $page->delete();

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Force delete a page permanently.
     *
     * @throws Exception
     */
    public function forceDeletePage(Page $page): bool
    {
        try {
            DB::beginTransaction();

            // Clear all media
            $page->clearMediaCollection('featured');
            $page->clearMediaCollection('gallery');

            // Force delete the page
            $deleted = $page->forceDelete();

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore a soft-deleted page.
     */
    public function restorePage(Page $page): bool
    {
        return $page->restore();
    }

    /**
     * Handle media upload for a page.
     */
    public function handleMedia(Page $page, UploadedFile $file, string $collection = 'featured'): void
    {
        $page->addMedia($file)
            ->toMediaCollection($collection);
    }

    /**
     * Generate a unique slug from a title.
     */
    public function generateSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        // Check if slug exists and make it unique
        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug exists.
     */
    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = Page::where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Publish a page.
     */
    public function publishPage(Page $page): Page
    {
        $page->publish();

        return $page->fresh();
    }

    /**
     * Unpublish a page.
     */
    public function unpublishPage(Page $page): Page
    {
        $page->unpublish();

        return $page->fresh();
    }

    /**
     * Duplicate a page.
     */
    public function duplicatePage(Page $page): Page
    {
        $newPage = $page->replicate();
        $newPage->title = $page->title.' (Copy)';
        $newPage->slug = $this->generateSlug($newPage->title);
        $newPage->status = Page::STATUS_DRAFT;
        $newPage->published_at = null;
        $newPage->save();

        // Copy media if exists
        if ($page->hasMedia('featured')) {
            $media = $page->getFirstMedia('featured');
            $newPage->addMedia($media->getPath())
                ->preservingOriginal()
                ->toMediaCollection('featured');
        }

        return $newPage;
    }

    /**
     * Get pages with filters.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPages(array $filters = [])
    {
        $query = Page::with('user');

        // Apply status filter
        if (isset($filters['status']) && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply template filter
        if (isset($filters['template']) && ! empty($filters['template'])) {
            $query->where('template', $filters['template']);
        }

        // Apply date range filter
        if (isset($filters['date_from']) && ! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && ! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $filters['per_page'] ?? config('page.per_page', 20);

        return $query->paginate($perPage);
    }
}
