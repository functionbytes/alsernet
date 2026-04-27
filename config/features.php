<?php

return [
    'ai_descriptions' => env('FEATURE_AI_DESCRIPTIONS', true),
    'advanced_recommendations' => env('FEATURE_ADVANCED_RECOMMENDATIONS', true),
    'chatbot_llm' => env('FEATURE_CHATBOT_LLM', false),
    'subscriptions' => env('FEATURE_SUBSCRIPTIONS', true),
    'bundles' => env('FEATURE_BUNDLES', true),
    'gift_cards' => env('FEATURE_GIFT_CARDS', true),
    'saved_searches' => env('FEATURE_SAVED_SEARCHES', true),
    'exit_intent' => env('FEATURE_EXIT_INTENT', true),
];
