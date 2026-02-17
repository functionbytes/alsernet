<?php

namespace Modules\Widget;

use Illuminate\Support\Collection;

class WidgetGroupCollection
{
    /**
     * Widget groups
     *
     * @var Collection
     */
    protected $groups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new Collection;
    }

    /**
     * Register a widget group
     */
    public function group(string $name, ?string $title = null, ?string $description = null): WidgetGroup
    {
        if (! $this->hasGroup($name)) {
            $this->groups->put($name, new WidgetGroup($name, $title, $description));
        }

        return $this->groups->get($name);
    }

    /**
     * Register a widget to a group
     *
     * @return $this
     */
    public function registerWidget(string $groupName, string $widgetClass, array $config = []): self
    {
        $group = $this->group($groupName);
        $group->addWidget($widgetClass, $config);

        return $this;
    }

    /**
     * Remove a widget from a group
     *
     * @return $this
     */
    public function removeWidget(string $groupName, string $widgetClass): self
    {
        if ($this->hasGroup($groupName)) {
            $this->groups->get($groupName)->removeWidget($widgetClass);
        }

        return $this;
    }

    /**
     * Get a widget group
     */
    public function getGroup(string $name): ?WidgetGroup
    {
        return $this->groups->get($name);
    }

    /**
     * Get all groups
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    /**
     * Check if group exists
     */
    public function hasGroup(string $name): bool
    {
        return $this->groups->has($name);
    }

    /**
     * Remove a group
     *
     * @return $this
     */
    public function removeGroup(string $name): self
    {
        $this->groups->forget($name);

        return $this;
    }

    /**
     * Get all widgets from all groups
     */
    public function getAllWidgets(): Collection
    {
        $widgets = new Collection;

        $this->groups->each(function (WidgetGroup $group) use ($widgets) {
            $group->getWidgets()->each(function ($widget, $id) use ($widgets, $group) {
                $widgets->put($id, array_merge($widget, [
                    'group' => $group->getName(),
                ]));
            });
        });

        return $widgets;
    }

    /**
     * Get widgets by group name
     */
    public function getWidgetsByGroup(string $groupName): Collection
    {
        if (! $this->hasGroup($groupName)) {
            return new Collection;
        }

        return $this->groups->get($groupName)->getWidgets();
    }

    /**
     * Clear all groups
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->groups = new Collection;

        return $this;
    }
}
