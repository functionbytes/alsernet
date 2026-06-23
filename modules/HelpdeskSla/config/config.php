<?php

return [
    'name' => 'HelpdeskSla',

    /*
    |--------------------------------------------------------------------------
    | Cadencia del scheduler
    |--------------------------------------------------------------------------
    | Cada cuanto se ejecuta la deteccion de incumplimientos de SLA sobre las
    | conversaciones abiertas. El comando helpdesksla:check-breaches se programa
    | en el ServiceProvider.
    */
    'check_interval_minutes' => 5,

    /*
    | Cada cuanto se ejecuta el aviso previo al incumplimiento (warning).
    */
    'warning_interval_minutes' => 15,

    /*
    |--------------------------------------------------------------------------
    | Horario laboral por defecto
    |--------------------------------------------------------------------------
    | Usado para calcular vencimientos cuando la policy tiene business_hours_only
    | y no hay filas en helpdesk_business_hours. day_of_week: 0=Domingo .. 6=Sabado.
    */
    'default_business_hours' => [
        'timezone' => 'Europe/Madrid',
        'days' => [
            1 => ['open' => '09:00', 'close' => '18:00'],
            2 => ['open' => '09:00', 'close' => '18:00'],
            3 => ['open' => '09:00', 'close' => '18:00'],
            4 => ['open' => '09:00', 'close' => '18:00'],
            5 => ['open' => '09:00', 'close' => '18:00'],
        ],
    ],
];
