<?php

namespace Modules\Widget\Factories;

use Modules\Widget\AbstractWidget;
use Modules\Widget\WidgetId;

abstract class AbstractWidgetFactory
{
    /**
     * Registered widgets
     *
     * @var array
     */
    protected $widgets = [];

    /**
     * Register a widget
     *
     * @return $this
     */
    public function register(string $widgetClass): self
    {
        if (! class_exists($widgetClass)) {
            return $this;
        }

        if (! is_subclass_of($widgetClass, AbstractWidget::class)) {
            return $this;
        }

        $widgetId = WidgetId::fromClassName($widgetClass);

        $this->widgets[$widgetId->getId()] = $widgetClass;

        return $this;
    }

    /**
     * Make a widget instance
     */
    public function make(string $widgetClass, array $config = []): ?AbstractWidget
    {
        if (! class_exists($widgetClass)) {
            return null;
        }

        if (! is_subclass_of($widgetClass, AbstractWidget::class)) {
            return null;
        }

        return new $widgetClass($config);
    }

    /**
     * Get all registered widgets
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * Check if widget is registered
     */
    public function isRegistered(string $widgetClass): bool
    {
        $widgetId = WidgetId::fromClassName($widgetClass);

        return isset($this->widgets[$widgetId->getId()]);
    }

    /**
     * Unregister a widget
     *
     * @return $this
     */
    public function unregister(string $widgetClass): self
    {
        $widgetId = WidgetId::fromClassName($widgetClass);

        unset($this->widgets[$widgetId->getId()]);

        return $this;
    }
}
