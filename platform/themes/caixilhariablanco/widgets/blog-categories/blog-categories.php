<?php

use Modules\Widget\AbstractWidget;

class BlogCategoriesWidget extends AbstractWidget
{
    public function __construct(array $config = [])
    {
        parent::__construct(array_merge([
            'name' => __('Blog Categories'),
            'number_display' => 10,
        ], $config));
    }

    public static function group(): string
    {
        return 'blog';
    }

    protected function getViewPath(string $template): string
    {
        return 'template::widgets.blog-categories.templates.'.$template;
    }

    protected function data(): array
    {
        return ['config' => $this->config];
    }
}
