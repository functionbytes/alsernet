<?php

namespace Modules\Widget\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Modules\Widget\Models\Widget;

interface WidgetInterface
{
    /**
     * Get all widgets
     */
    public function all(): Collection;

    /**
     * Find widget by ID
     */
    public function find(int $id): ?Widget;

    /**
     * Create a new widget
     */
    public function create(array $data): Widget;

    /**
     * Update widget
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete widget
     */
    public function delete(int $id): bool;

    /**
     * Get widgets by sidebar
     */
    public function getBySidebar(string $sidebarId): Collection;

    /**
     * Get widgets by theme
     */
    public function getByTheme(string $theme): Collection;

    /**
     * Get active widgets
     */
    public function getActive(): Collection;

    /**
     * Get widgets by sidebar and theme
     */
    public function getBySidebarAndTheme(string $sidebarId, string $theme): Collection;

    /**
     * Update widget position
     */
    public function updatePosition(int $id, int $position): bool;

    /**
     * Toggle widget status
     */
    public function toggleStatus(int $id): bool;

    /**
     * Delete widgets by sidebar
     */
    public function deleteBySidebar(string $sidebarId): bool;
}
