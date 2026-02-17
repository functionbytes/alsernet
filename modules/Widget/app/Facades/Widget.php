<?php

namespace Modules\Widget\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Modules\Widget\Factories\WidgetFactory register(string $widgetClass)
 * @method static \Modules\Widget\AbstractWidget|null make(string $widgetClass, array $config = [])
 * @method static array getWidgets()
 * @method static bool isRegistered(string $widgetClass)
 * @method static \Modules\Widget\Factories\WidgetFactory unregister(string $widgetClass)
 * @method static \Modules\Widget\Factories\WidgetFactory discover(string $path, string $namespace)
 * @method static string render(string $widgetClass, array $config = [])
 * @method static string renderBackend(string $widgetClass, array $config = [])
 * @method static array getWidgetData(string $widgetClass)
 *
 * @see \Modules\Widget\Factories\WidgetFactory
 */
class Widget extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'widget.factory';
    }
}
