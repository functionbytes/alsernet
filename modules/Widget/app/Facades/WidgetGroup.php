<?php

namespace Modules\Widget\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Modules\Widget\WidgetGroup group(string $name, string|null $title = null, string|null $description = null)
 * @method static \Modules\Widget\WidgetGroupCollection registerWidget(string $groupName, string $widgetClass, array $config = [])
 * @method static \Modules\Widget\WidgetGroupCollection removeWidget(string $groupName, string $widgetClass)
 * @method static \Modules\Widget\WidgetGroup|null getGroup(string $name)
 * @method static \Illuminate\Support\Collection getGroups()
 * @method static bool hasGroup(string $name)
 * @method static \Modules\Widget\WidgetGroupCollection removeGroup(string $name)
 * @method static \Illuminate\Support\Collection getAllWidgets()
 * @method static \Illuminate\Support\Collection getWidgetsByGroup(string $groupName)
 * @method static \Modules\Widget\WidgetGroupCollection clear()
 *
 * @see \Modules\Widget\WidgetGroupCollection
 */
class WidgetGroup extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'widget.groups';
    }
}
