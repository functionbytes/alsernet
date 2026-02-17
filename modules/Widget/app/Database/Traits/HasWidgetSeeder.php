<?php

namespace Modules\Widget\Database\Traits;

use Modules\Widget\Models\Widget;

trait HasWidgetSeeder
{
    /**
     * Create a widget
     */
    protected function createWidget(array $data): Widget
    {
        return Widget::create($data);
    }

    /**
     * Create multiple widgets
     */
    protected function createWidgets(array $widgets): void
    {
        foreach ($widgets as $widget) {
            $this->createWidget($widget);
        }
    }

    /**
     * Seed default sidebar widgets
     */
    protected function seedSidebarWidgets(string $sidebarId, string $theme, array $widgets): void
    {
        $position = 0;

        foreach ($widgets as $widgetId => $config) {
            $this->createWidget([
                'widget_id' => $widgetId,
                'sidebar_id' => $sidebarId,
                'theme' => $theme,
                'position' => $position++,
                'data' => $config,
                'status' => true,
            ]);
        }
    }

    /**
     * Clear widgets by sidebar
     */
    protected function clearSidebarWidgets(string $sidebarId): void
    {
        Widget::where('sidebar_id', $sidebarId)->delete();
    }

    /**
     * Clear widgets by theme
     */
    protected function clearThemeWidgets(string $theme): void
    {
        Widget::where('theme', $theme)->delete();
    }

    /**
     * Clear all widgets
     */
    protected function clearAllWidgets(): void
    {
        Widget::truncate();
    }

    /**
     * Update widget position
     */
    protected function updateWidgetPosition(string $widgetId, string $sidebarId, int $position): void
    {
        Widget::where('widget_id', $widgetId)
            ->where('sidebar_id', $sidebarId)
            ->update(['position' => $position]);
    }

    /**
     * Toggle widget status
     */
    protected function toggleWidgetStatus(string $widgetId, string $sidebarId): void
    {
        $widget = Widget::where('widget_id', $widgetId)
            ->where('sidebar_id', $sidebarId)
            ->first();

        if ($widget) {
            $widget->update(['status' => ! $widget->status]);
        }
    }

    /**
     * Get default widget configuration
     */
    protected function getDefaultWidgetConfig(): array
    {
        return [
            'cache' => true,
            'cache_lifetime' => 3600,
        ];
    }
}
