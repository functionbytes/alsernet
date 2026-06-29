<?php

use Modules\Widget\AbstractWidget;

class FeaturedProductsWidget extends AbstractWidget
{
    public static function group(): string
    {
        return 'general';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => __('Featured Products'),
            'description' => __('Widget display featured products'),
            'number_display' => 3,
        ]);
    }
}
