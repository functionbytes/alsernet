<?php

return [
    'name' => 'HelpdeskAgents',

    'llm_rate_limits' => [
        'per_user_per_minute' => 10,
        'per_session_per_5min' => 30,
        'per_user_per_day' => 1000,
    ],

    'prompt_injection_patterns' => [
        '/ignore\s+(all\s+)?previous\s+instructions/i',
        '/system\s+prompt/i',
        '/reveal\s+your\s+(system\s+)?prompt/i',
        '/you\s+are\s+(now\s+)?a/i',
    ],
];
