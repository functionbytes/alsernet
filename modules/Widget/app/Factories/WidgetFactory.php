<?php

namespace Modules\Widget\Factories;

use Illuminate\Support\Facades\File;
use Modules\Widget\AbstractWidget;

class WidgetFactory extends AbstractWidgetFactory
{
    /**
     * Auto-discover and register widgets
     *
     * @return $this
     */
    public function discover(string $path, string $namespace): self
    {
        if (! File::isDirectory($path)) {
            return $this;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            $class = $namespace.'\\'.str_replace(
                ['/', '.php'],
                ['\\', ''],
                $file->getRelativePathname()
            );

            if (class_exists($class) && is_subclass_of($class, AbstractWidget::class)) {
                $this->register($class);
            }
        }

        return $this;
    }

    /**
     * Render a widget
     */
    public function render(string $widgetClass, array $config = []): string
    {
        $widget = $this->make($widgetClass, $config);

        if (! $widget) {
            return '';
        }

        return $widget->run();
    }

    /**
     * Render widget backend
     */
    public function renderBackend(string $widgetClass, array $config = []): string
    {
        $widget = $this->make($widgetClass, $config);

        if (! $widget) {
            return '';
        }

        return $widget->backend();
    }

    /**
     * Get widget data
     */
    public function getWidgetData(string $widgetClass): array
    {
        $widget = $this->make($widgetClass);

        if (! $widget) {
            return [];
        }

        return [
            'title' => $widget->title(),
            'group' => $widget->group(),
            'is_core' => $widget->isCoreWidget(),
        ];
    }
}
