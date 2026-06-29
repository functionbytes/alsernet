<?php

use Modules\Widget\AbstractWidget;

class PaymentMethodsWidget extends AbstractWidget
{
    public static function group(): string
    {
        return 'general';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => __('Payments'),
            'description' => __('Widget display accepted payment methods.'),
            'image' => null,
        ]);
    }
}
