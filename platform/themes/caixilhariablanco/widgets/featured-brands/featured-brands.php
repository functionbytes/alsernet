<?php

use Modules\Widget\AbstractWidget;

class FeaturedBrandsWidget extends AbstractWidget
{
    public static function group(): string
    {
        return 'general';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => __('Featured Brands'),
            'description' => __('Widget display featured brands'),
            'number_display' => 10,
        ]);
    }
}
