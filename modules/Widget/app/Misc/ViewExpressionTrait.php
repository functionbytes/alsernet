<?php

namespace Modules\Widget\Misc;

use Illuminate\Support\Facades\Blade;

trait ViewExpressionTrait
{
    /**
     * Register custom Blade directives for widgets
     */
    protected function registerWidgetDirectives(): void
    {
        // @widget('WidgetClass', ['config' => 'value'])
        Blade::directive('widget', function ($expression) {
            return "<?php echo app('widget.factory')->render({$expression}); ?>";
        });

        // @widgetGroup('group-name')
        Blade::directive('widgetGroup', function ($expression) {
            return "<?php echo \Modules\Widget\Facades\WidgetGroup::getGroup({$expression})?->getWidgets()->map(function(\$widget) {
                return app('widget.factory')->render(\$widget['class'], \$widget['config'] ?? []);
            })->implode(''); ?>";
        });

        // @renderWidget($widgetInstance)
        Blade::directive('renderWidget', function ($expression) {
            return "<?php echo {$expression}->run(); ?>";
        });

        // @widgetSettings('WidgetClass', ['config' => 'value'])
        Blade::directive('widgetSettings', function ($expression) {
            return "<?php echo app('widget.factory')->renderBackend({$expression}); ?>";
        });
    }

    /**
     * Create a view expression for widget rendering
     */
    protected function widgetExpression(string $widgetClass, array $config = []): string
    {
        $configJson = json_encode($config);

        return "app('widget.factory')->render('{$widgetClass}', {$configJson})";
    }

    /**
     * Create a view expression for widget group rendering
     */
    protected function widgetGroupExpression(string $groupName): string
    {
        return "\\Modules\\Widget\\Facades\\WidgetGroup::getGroup('{$groupName}')?->getWidgets()->map(function(\$widget) {
            return app('widget.factory')->render(\$widget['class'], \$widget['config'] ?? []);
        })->implode('')";
    }
}
