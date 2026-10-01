<?php

return [
    'name' => 'HelpdeskAiPrompts',

    /*
     | Precios públicos de OpenAI en USD por 1M de tokens. Es una ESTIMACIÓN
     | (no la factura real): sirve para comparar casos entre sí. Se pueden
     | sobrescribir por .env; los modelos con sufijo de versión
     | (gpt-4o-mini-2024-07-18) usan el precio del prefijo más largo.
     */
    'pricing' => [
        'usd_to_eur' => (float) env('AI_PRICING_USD_TO_EUR', 0.92),
        'models' => [
            'gpt-4o-mini' => [
                'input' => (float) env('AI_PRICE_GPT_4O_MINI_INPUT', 0.15),
                'output' => (float) env('AI_PRICE_GPT_4O_MINI_OUTPUT', 0.60),
            ],
            'gpt-4o' => [
                'input' => (float) env('AI_PRICE_GPT_4O_INPUT', 2.50),
                'output' => (float) env('AI_PRICE_GPT_4O_OUTPUT', 10.00),
            ],
        ],
        // Modelo desconocido: se estima con este precio.
        'fallback_model' => 'gpt-4o-mini',
    ],
];
