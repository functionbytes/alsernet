<?php

namespace Modules\Widget;

use Illuminate\Support\Collection;

class WidgetGroup
{
    /**
     * Group name
     *
     * @var string
     */
    protected $name;

    /**
     * Group title
     *
     * @var string
     */
    protected $title;

    /**
     * Group description
     *
     * @var string|null
     */
    protected $description;

    /**
     * Widgets in this group
     *
     * @var Collection
     */
    protected $widgets;

    /**
     * Constructor
     */
    public function __construct(string $name, ?string $title = null, ?string $description = null)
    {
        $this->name = $name;
        $this->title = $title ?: ucfirst($name);
        $this->description = $description;
        $this->widgets = new Collection;
    }

    /**
     * Get group name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get group title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get group description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Add a widget to this group
     *
     * @return $this
     */
    public function addWidget(string $widgetClass, array $config = []): self
    {
        $widgetId = WidgetId::fromClassName($widgetClass);

        $this->widgets->put($widgetId->getId(), [
            'class' => $widgetClass,
            'config' => $config,
        ]);

        return $this;
    }

    /**
     * Remove a widget from this group
     *
     * @return $this
     */
    public function removeWidget(string $widgetClass): self
    {
        $widgetId = WidgetId::fromClassName($widgetClass);

        $this->widgets->forget($widgetId->getId());

        return $this;
    }

    /**
     * Get all widgets in this group
     */
    public function getWidgets(): Collection
    {
        return $this->widgets;
    }

    /**
     * Check if widget exists in group
     */
    public function hasWidget(string $widgetClass): bool
    {
        $widgetId = WidgetId::fromClassName($widgetClass);

        return $this->widgets->has($widgetId->getId());
    }

    /**
     * Get widget by class name
     */
    public function getWidget(string $widgetClass): ?array
    {
        $widgetId = WidgetId::fromClassName($widgetClass);

        return $this->widgets->get($widgetId->getId());
    }

    /**
     * Get count of widgets in group
     */
    public function count(): int
    {
        return $this->widgets->count();
    }

    /**
     * Clear all widgets from group
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->widgets = new Collection;

        return $this;
    }
}
