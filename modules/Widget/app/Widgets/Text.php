<?php

namespace Modules\Widget\Widgets;

use Modules\Widget\AbstractWidget;

class Text extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => trans('widget::widget.widget_text'),
            'description' => trans('widget::widget.widget_text_description'),
            'content' => null,
        ]);

        $widgetDirectory = $this->getWidgetDirectory();

        $this->setFrontendTemplate('widget::widgets.'.$widgetDirectory.'.frontend');
        $this->setBackendTemplate('widget::widgets.'.$widgetDirectory.'.backend');
    }

    public function getWidgetDirectory(): string
    {
        return 'text';
    }
}
