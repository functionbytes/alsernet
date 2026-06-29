<?php

use Modules\Widget\AbstractWidget;

class RecentPostsWidget extends AbstractWidget
{
    public function __construct(array $config = [])
    {
        parent::__construct(array_merge([
            'name' => __('Recent Posts'),
            'number_display' => 4,
        ], $config));
    }

    public static function group(): string
    {
        return 'blog';
    }

    protected function getViewPath(string $template): string
    {
        return 'template::widgets.recent-posts.templates.'.$template;
    }

    protected function data(): array
    {
        return ['config' => $this->config];
    }
}
