<?php

use Modules\Widget\AbstractWidget;

class TagsWidget extends AbstractWidget
{
    public function __construct(array $config = [])
    {
        parent::__construct(array_merge([
            'name' => __('Our Tags'),
            'number_display' => 15,
        ], $config));
    }

    public static function group(): string
    {
        return 'blog';
    }

    protected function getViewPath(string $template): string
    {
        return 'template::widgets.tags.templates.'.$template;
    }

    protected function data(): array
    {
        return ['config' => $this->config];
    }
}
