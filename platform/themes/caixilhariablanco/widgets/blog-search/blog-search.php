<?php

use Modules\Widget\AbstractWidget;

class BlogSearchWidget extends AbstractWidget
{
    public function __construct(array $config = [])
    {
        parent::__construct(array_merge([
            'name' => __('Blog Search'),
            'number_display' => 10,
        ], $config));
    }

    public static function group(): string
    {
        return 'blog';
    }

    protected function getViewPath(string $template): string
    {
        return 'template::widgets.blog-search.templates.'.$template;
    }

    protected function data(): array
    {
        return ['config' => $this->config];
    }
}
