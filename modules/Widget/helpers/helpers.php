<?php

use Modules\Widget\Facades\Widget;
use Modules\Widget\Facades\WidgetGroup;
use Modules\Widget\Factories\WidgetFactory;
use Modules\Widget\WidgetGroupCollection;

if (! function_exists('register_widget')) {
    function register_widget(string $widgetId): WidgetFactory
    {
        return Widget::register($widgetId);
    }
}

if (! function_exists('register_sidebar')) {
    function register_sidebar(array $args): WidgetGroupCollection
    {
        return WidgetGroup::setGroup($args);
    }
}

if (! function_exists('remove_sidebar')) {
    function remove_sidebar(string $sidebarId): WidgetGroupCollection
    {
        return WidgetGroup::removeGroup($sidebarId);
    }
}

if (! function_exists('dynamic_sidebar')) {
    function dynamic_sidebar(string $sidebarId): string
    {
        return WidgetGroup::render($sidebarId);
    }
}
