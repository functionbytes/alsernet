<?php

namespace Modules\Widget;

use Illuminate\Support\Collection;
use Modules\Widget\Facades\Widget;

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
     * Register a group using a WordPress-style args array (id, name, description)
     *
     * @return $this
     */
    public function setGroup(array $args): self
    {
        $id = $args['id'] ?? $args['name'] ?? '';
        $name = $args['name'] ?? $id;
        $description = $args['description'] ?? null;

        if ($id) {
            $this->group($id, $name, $description);
        }

        return $this;
    }

    /**
     * Render a sidebar/widget group by its ID.
     * Loads widget assignments from the database and maps them to registered widget classes.
     */
    public function render(string $sidebarId): string
    {
        $registeredWidgets = Widget::getWidgets();

        if (empty($registeredWidgets)) {
            return '';
        }

        $theme = function_exists('setting') ? setting('template', '') : '';

        $dbWidgets = Models\Widget::query()
            ->where('sidebar_id', $sidebarId)
            ->where('theme', $theme)
            ->orderBy('position')
            ->get();

        $output = '';

        foreach ($dbWidgets as $dbWidget) {
            $class = $registeredWidgets[$dbWidget->widget_id] ?? null;

            if (! $class || ! class_exists($class)) {
                continue;
            }

            $config = is_array($dbWidget->data) ? $dbWidget->data : [];
            $instance = new $class($config);
            $output .= $instance->run();
        }

        return $output;
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
