<?php

namespace Modules\Widget\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\Widget\Models\Widget;
use Modules\Widget\Repositories\Interfaces\WidgetInterface;

class WidgetRepository implements WidgetInterface
{
    /**
     * Widget model instance
     *
     * @var Widget
     */
    protected $model;

    /**
     * Constructor
     */
    public function __construct(Widget $model)
    {
        $this->model = $model;
    }

    /**
     * Get all widgets
     */
    public function all(): Collection
    {
        return $this->model->ordered()->get();
    }

    /**
     * Find widget by ID
     */
    public function find(int $id): ?Widget
    {
        return $this->model->find($id);
    }

    /**
     * Create a new widget
     */
    public function create(array $data): Widget
    {
        return $this->model->create($data);
    }

    /**
     * Update widget
     */
    public function update(int $id, array $data): bool
    {
        $widget = $this->find($id);

        if (! $widget) {
            return false;
        }

        return $widget->update($data);
    }

    /**
     * Delete widget
     */
    public function delete(int $id): bool
    {
        $widget = $this->find($id);

        if (! $widget) {
            return false;
        }

        return $widget->delete();
    }

    /**
     * Get widgets by sidebar
     */
    public function getBySidebar(string $sidebarId): Collection
    {
        return $this->model
            ->bySidebar($sidebarId)
            ->ordered()
            ->get();
    }

    /**
     * Get widgets by theme
     */
    public function getByTheme(string $theme): Collection
    {
        return $this->model
            ->byTheme($theme)
            ->ordered()
            ->get();
    }

    /**
     * Get active widgets
     */
    public function getActive(): Collection
    {
        return $this->model
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Get widgets by sidebar and theme
     */
    public function getBySidebarAndTheme(string $sidebarId, string $theme): Collection
    {
        return $this->model
            ->bySidebar($sidebarId)
            ->byTheme($theme)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Update widget position
     */
    public function updatePosition(int $id, int $position): bool
    {
        return $this->update($id, ['position' => $position]);
    }

    /**
     * Toggle widget status
     */
    public function toggleStatus(int $id): bool
    {
        $widget = $this->find($id);

        if (! $widget) {
            return false;
        }

        return $widget->update(['status' => ! $widget->status]);
    }

    /**
     * Delete widgets by sidebar
     */
    public function deleteBySidebar(string $sidebarId): bool
    {
        return $this->model->bySidebar($sidebarId)->delete();
    }
}
