<?php

use Modules\Widget\AbstractWidget;

class NewsletterWidget extends AbstractWidget
{
    public static function group(): string
    {
        return 'general';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => __('Newsletter'),
            'subtitle' => __('Subtitle'),
            'description' => __('Widget description'),
        ]);
    }
}
