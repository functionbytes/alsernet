<?php

return [

    // Cart recovery
    'cart_recovery' => [
        'subject' => 'Did you forget something in your cart?',
        'heading' => 'Hi :first_name, did you forget something?',
        'body' => 'We noticed you left these items in your cart. They are still waiting for you!',
        'total' => 'Total',
        'cta' => 'Complete my purchase',
    ],

    // Price drop
    'price_drop' => [
        'subject' => 'Price dropped on a product you wanted',
        'heading' => 'Hi :first_name',
        'body' => 'The product you had in mind has dropped in price. Now is the perfect time to buy it!',
        'was' => 'Was',
        'now' => 'Now',
        'discount' => '-:percent% off',
        'cta' => 'View product',
    ],

    // Back in stock
    'back_in_stock' => [
        'subject' => 'Back in stock: the product you were waiting for',
        'heading' => 'Hi :first_name',
        'body' => 'Great news! The product you were waiting for is back in stock. Don\'t wait too long, stock may run out soon.',
        'available_now' => 'Available now',
        'cta' => 'Buy now',
    ],

    // Replenishment
    'replenishment' => [
        'subject' => 'Time to restock?',
        'heading' => 'Hi :first_name',
        'body' => 'It\'s been a while since your last purchase of this product. Based on your buying habits, it might be a good time to reorder.',
        'subtitle' => 'Your usual order ready in just a few clicks. No waiting, no hassle.',
        'cta' => 'Reorder now',
    ],

    // Win-back
    'win_back' => [
        'subject' => 'We miss you — exclusive discount inside',
        'heading' => 'Hi :first_name',
        'body' => 'It\'s been a while since we\'ve seen you. To celebrate your return, we have an exclusive discount waiting for you.',
        'coupon_label' => 'Your exclusive discount',
        'coupon_description' => '10% off your next order',
        'limited' => 'This discount is valid for a limited time.',
        'cta' => 'Return to the store',
    ],

    // Double opt-in
    'double_optin' => [
        'subject' => 'Confirm your subscription',
        'greeting' => 'Hi:name,',
        'body' => 'We received your request to subscribe to our marketing communications. To confirm and start receiving our emails, click the button below.',
        'cta' => 'Confirm subscription',
        'fallback' => 'If you cannot click the button, copy and paste this link into your browser:',
        'ignore' => 'If you did not request this subscription, please ignore this email. We will not take any action without your confirmation.',
    ],

    // Common footer
    'common' => [
        'unsubscribe' => 'If you no longer wish to receive these reminders, you can :link at any time.',
        'unsubscribe_link' => 'unsubscribe',
    ],

];
