<?php

return [
    // Setting Mailer Test
    'setting' => [
        'mailer' => [
            'test' => [
                'email_subject' => 'Prueba de configuración del servidor de correo',
                'title' => 'Prueba del servidor de correo',
                'message' => 'Este es un correo de prueba para verificar que la configuración de su servidor de correo funciona correctamente.',
                'success' => 'Si está leyendo este mensaje, su configuración de correo electrónico funciona correctamente.',
                'details' => 'Detalles de la prueba:',
                'sent_at' => 'Enviado el:',
                'mailer' => 'Servidor:',
            ],
        ],
    ],

    // Subscription Done
    'subscription_done' => [
        'email_subject' => 'Suscripción confirmada - Bienvenido',
        'confirmed' => 'Confirmado',
        'title' => 'Suscripción completada',
        'greeting' => 'Hola :name,',
        'message' => 'Gracias por tu suscripción. Tu cuenta ha sido activada exitosamente.',
        'details_title' => 'Detalles de la suscripción',
        'customer' => 'Cliente',
        'plan' => 'Plan',
        'status' => 'Estado',
        'active' => 'Activo',
        'date' => 'Fecha',
        'next_steps' => 'Ahora puedes acceder a todas las funciones de tu plan. Haz clic en el botón a continuación para comenzar.',
        'view_dashboard' => 'Ver panel',
        'questions' => 'Si tienes alguna pregunta o necesitas ayuda, no dudes en contactar a nuestro equipo de soporte.',
    ],

    // General
    'all_rights_reserved' => 'Todos los derechos reservados.',
];
