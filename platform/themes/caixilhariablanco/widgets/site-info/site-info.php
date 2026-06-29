<?php

use Modules\Widget\AbstractWidget;

class SiteInfoWidget extends AbstractWidget
{
    public static function group(): string
    {
        return 'general';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => __('Site information'),
            'description' => __('Widget display site information'),
        ]);
    }
}
