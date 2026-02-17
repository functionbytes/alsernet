<?php

namespace Modules\Widget\Repositories\Caches;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Widget\Models\Widget;
use Modules\Widget\Repositories\Interfaces\WidgetInterface;

class WidgetCacheDecorator implements WidgetInterface
{
    /**
     * Repository instance
     *
     * @var WidgetInterface
     */
    protected $repository;

    /**
     * Cache lifetime in seconds
     *
     * @var int
     */
    protected $cacheLifetime = 3600;

    /**
     * Constructor
     */
    public function __construct(WidgetInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all widgets
     */
    public function all(): Collection
    {
        return Cache::remember('widgets.all', $this->cacheLifetime, function () {
            return $this->repository->all();
        });
    }

    /**
     * Find widget by ID
     */
    public function find(int $id): ?Widget
    {
        return Cache::remember("widgets.{$id}", $this->cacheLifetime, function () use ($id) {
            return $this->repository->find($id);
        });
    }

    /**
     * Create a new widget
     */
    public function create(array $data): Widget
    {
        $this->clearCache();

        return $this->repository->create($data);
    }

    /**
     * Update widget
     */
    public function update(int $id, array $data): bool
    {
        $this->clearCache();

        return $this->repository->update($id, $data);
    }

    /**
     * Delete widget
     */
    public function delete(int $id): bool
    {
        $this->clearCache();

        return $this->repository->delete($id);
    }

    /**
     * Get widgets by sidebar
     */
    public function getBySidebar(string $sidebarId): Collection
    {
        return Cache::remember("widgets.sidebar.{$sidebarId}", $this->cacheLifetime, function () use ($sidebarId) {
            return $this->repository->getBySidebar($sidebarId);
        });
    }

    /**
     * Get widgets by theme
     */
    public function getByTheme(string $theme): Collection
    {
        return Cache::remember("widgets.theme.{$theme}", $this->cacheLifetime, function () use ($theme) {
            return $this->repository->getByTheme($theme);
        });
    }

    /**
     * Get active widgets
     */
    public function getActive(): Collection
    {
        return Cache::remember('widgets.active', $this->cacheLifetime, function () {
            return $this->repository->getActive();
        });
    }

    /**
     * Get widgets by sidebar and theme
     */
    public function getBySidebarAndTheme(string $sidebarId, string $theme): Collection
    {
        return Cache::remember("widgets.sidebar.{$sidebarId}.theme.{$theme}", $this->cacheLifetime, function () use ($sidebarId, $theme) {
            return $this->repository->getBySidebarAndTheme($sidebarId, $theme);
        });
    }

    /**
     * Update widget position
     */
    public function updatePosition(int $id, int $position): bool
    {
        $this->clearCache();

        return $this->repository->updatePosition($id, $position);
    }

    /**
     * Toggle widget status
     */
    public function toggleStatus(int $id): bool
    {
        $this->clearCache();

        return $this->repository->toggleStatus($id);
    }

    /**
     * Delete widgets by sidebar
     */
    public function deleteBySidebar(string $sidebarId): bool
    {
        $this->clearCache();

        return $this->repository->deleteBySidebar($sidebarId);
    }

    /**
     * Clear widget cache
     */
    protected function clearCache(): void
    {
        Cache::forget('widgets');
        Cache::flush();
    }
}
