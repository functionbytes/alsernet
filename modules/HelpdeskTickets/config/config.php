<?php

return [
    'name' => 'HelpdeskTickets',

    /*
    |--------------------------------------------------------------------------
    | Razones de CSAT
    |--------------------------------------------------------------------------
    | Lista de razones de insatisfacción que se ofrecen al cliente cuando valora
    | bajo (rating <= csat_reason_threshold). key => etiqueta. El desglose por
    | razón aparece en el reporte de CSAT.
    */
    'csat_reason_threshold' => 3,

    'csat_reasons' => [
        'slow' => 'Tardó demasiado en resolverse',
        'unresolved' => 'No resolvió mi problema',
        'unfriendly' => 'Trato poco amable',
        'repeated_info' => 'Tuve que repetir la información varias veces',
        'unclear' => 'La respuesta fue poco clara',
        'other' => 'Otro motivo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Motivos de cierre
    |--------------------------------------------------------------------------
    | Mismas claves/etiquetas que el modal "Cerrar conversación" del módulo
    | Helpdesk (close-conv.blade.php) — se reutilizan tal cual para que un
    | motivo signifique lo mismo en Conversaciones y en Tickets.
    */
    'close_reasons' => [
        'resolved' => 'Resuelto',
        'duplicated' => 'Duplicado',
        'spam' => 'Spam / no procede',
        'unresponsive' => 'Sin respuesta del cliente',
        'other' => 'Otro motivo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Motor de escalado de tickets (EscalateTicketsJob / EscalationService)
    |--------------------------------------------------------------------------
    | Los umbrales base por prioridad y el interruptor global viven en
    | config('helpdesk.escalation.*') (módulo Helpdesk). Aquí van los ajustes
    | propios del motor de este módulo.
    */
    'escalation' => [
        // Escalar también por SLA de resolución (vencido o próximo a vencer).
        'sla_enabled' => env('HELPDESK_ESCALATION_SLA_ENABLED', true),

        // Minutos antes del vencimiento del SLA de resolución a partir de los
        // cuales el ticket se considera "próximo a vencer" y se escala.
        'sla_due_within_minutes' => env('HELPDESK_ESCALATION_SLA_DUE_WITHIN_MINUTES', 60),

        // Horas mínimas entre dos escalados del mismo ticket.
        'cooldown_hours' => env('HELPDESK_ESCALATION_COOLDOWN_HOURS', 24),

        // Número máximo de escalados por ticket (escalation_count).
        'max_escalations' => env('HELPDESK_ESCALATION_MAX', 3),

        // Notificar a los managers además del agente asignado.
        'notify_managers' => env('HELPDESK_ESCALATION_NOTIFY_MANAGERS', true),

        // Roles que reciben la notificación de escalado.
        'notify_roles' => ['manager'],

        // Evaluar los umbrales de antigüedad (thresholds por prioridad) en
        // horas HÁBILES usando el calendario helpdesk_business_hours de
        // HelpdeskSla (BusinessHoursCalculator). OFF por defecto = horas
        // naturales, comportamiento histórico. Dependencia blanda: si el
        // módulo HelpdeskSla no está presente se ignora y se usan horas
        // naturales aunque esté activado.
        'business_hours' => env('HELPDESK_ESCALATION_BUSINESS_HOURS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Informes programados por email (helpdesk:send-scheduled-reports)
    |--------------------------------------------------------------------------
    | OFF por defecto. Con el toggle activo, el scheduler envía un informe HTML
    | del periodo (semanal: lunes / mensual: día 1) con el resumen de tickets,
    | CSAT y salud operativa (OpsHealthService), reutilizando las mismas
    | queries del dashboard de reports (TicketReportsService) y el exporter CSV
    | compartido para el adjunto opcional.
    */
    /*
    |--------------------------------------------------------------------------
    | Lecturas en lenguaje natural (TicketInsightsService)
    |--------------------------------------------------------------------------
    | Redacta el "qué ha pasado" del periodo y agrupa por temas los comentarios
    | de CSAT. No añade consultas: trabaja sobre lo que ya calcula
    | TicketReportsService. Inerte sin un agente IA configurado.
    */
    'insights' => [
        'enabled' => env('HELPDESKTICKETS_INSIGHTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guardia de salida (ReplyGuardService)
    |--------------------------------------------------------------------------
    | Revisa el borrador antes de enviarlo al cliente y avisa de datos que no
    | están en el hilo o promesas que no respalda ninguna plantilla. NUNCA
    | bloquea el envío: si la revisión falla o no hay agente IA, la respuesta
    | sale igual.
    */
    'reply_guard' => [
        'enabled' => env('HELPDESKTICKETS_REPLY_GUARD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deflexión en el portal (TicketDeflectionService)
    |--------------------------------------------------------------------------
    | Sugiere artículos del centro de ayuda mientras el cliente redacta, antes
    | de crear el ticket. Nunca impide abrirlo. Sin centro de ayuda instalado
    | no hace nada; sin agente IA, muestra los resultados del buscador sin
    | filtrar.
    */
    'deflection' => [
        'enabled' => env('HELPDESKTICKETS_DEFLECTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Clasificador de spam (SpamClassifierService)
    |--------------------------------------------------------------------------
    | Complementa a la lista negra, que solo bloquea remitentes ya conocidos.
    | RETIENE en cuarentena, nunca descarta, y solo mira remitentes SIN tickets
    | previos — quien ya es cliente no es spam, y saltárselo elimina la mayor
    | parte del coste.
    |
    | OFF por defecto y con umbral alto: un falso positivo es un cliente real
    | cuyo correo se queda fuera. Es más barato cerrar un ticket basura que
    | descubrir tarde que se retuvo un pedido grande.
    */
    /*
    |--------------------------------------------------------------------------
    | Escalado por riesgo (TicketRiskScoreService)
    |--------------------------------------------------------------------------
    | EscalationService escala por reloj: 48/24/12 horas según prioridad. Esto
    | pondera señales que el sistema ya calcula —sentimiento del cliente,
    | reaperturas, SLA, mensajes sin responder, valoraciones previas— y ACORTA
    | ese plazo cuando el ticket lo pide. Nunca lo alarga: activarlo no puede
    | retrasar ningún escalado que ya ocurría.
    |
    | No hace ninguna llamada al modelo: el sentimiento se lo dio el LLM cuando
    | el ticket entró, y aquí solo se combina.
    |
    | min_score: por debajo de esto el plazo no se toca.
    | max_reduction: plazo mínimo como fracción del original (0.25 = a un cuarto).
    */
    'risk' => [
        'enabled' => env('HELPDESKTICKETS_RISK_ESCALATION', false),
        'min_score' => (float) env('HELPDESKTICKETS_RISK_MIN_SCORE', 0.4),
        'max_reduction' => (float) env('HELPDESKTICKETS_RISK_MAX_REDUCTION', 0.25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Borradores de articulo (ArticleDraftService)
    |--------------------------------------------------------------------------
    | Convierte grupos de tickets YA RESUELTOS que se repiten en borradores de
    | articulo del centro de ayuda, redactados a partir de las respuestas que
    | de verdad funcionaron. Cierra el circulo con la deflexion del portal:
    | cada articulo publicado evita los tickets siguientes.
    |
    | Siempre borrador, nunca publicado. category_id opcional: si se deja
    | vacio, los borradores quedan sin categoria para que quien revise decida.
    */
    /*
    |--------------------------------------------------------------------------
    | Conclusión de los hilos laterales
    |--------------------------------------------------------------------------
    | Al cerrar una conversación lateral (consulta a un proveedor u otro
    | departamento), deja su conclusión como nota interna en el ticket. La
    | respuesta ya estaba escrita, solo que en un hilo aparte que nadie relee
    | cuando el ticket cambia de manos.
    */
    'side_conversation_summary' => env('HELPDESKTICKETS_SIDE_SUMMARY', true),

    /*
    |--------------------------------------------------------------------------
    | Revision de calidad por muestreo (TicketQualityReviewService)
    |--------------------------------------------------------------------------
    | Muestrea tickets cerrados al azar y evalua la atencion dada. Da una medida
    | de calidad que NO depende de que el cliente conteste al CSAT — hoy la
    | unica que hay, y la responde una minoria.
    |
    | La muestra es pequena a proposito: el objetivo es una medida estable, no
    | revisarlo todo. Cada revision es una llamada.
    |
    | Toda revision se puede disputar: una evaluacion automatica del trabajo de
    | una persona sin derecho a replica no es una metrica, es un juicio.
    */
    'quality_review' => [
        'enabled' => env('HELPDESKTICKETS_QUALITY_REVIEW', false),
        'daily_sample' => (int) env('HELPDESKTICKETS_QUALITY_SAMPLE', 10),
    ],

    'article_drafts' => [
        'enabled' => env('HELPDESKTICKETS_ARTICLE_DRAFTS', false),
        'category_id' => env('HELPDESKTICKETS_ARTICLE_DRAFTS_CATEGORY'),
    ],

    'spam_classifier' => [
        'enabled' => env('HELPDESKTICKETS_SPAM_CLASSIFIER', false),
        'threshold' => (float) env('HELPDESKTICKETS_SPAM_THRESHOLD', 0.9),
    ],

    'reports' => [
        'scheduled' => [
            'enabled' => env('HELPDESK_SCHEDULED_REPORTS_ENABLED', false),

            // 'weekly' (periodo: últimos 7 días) o 'monthly' (últimos 30 días).
            'frequency' => env('HELPDESK_SCHEDULED_REPORTS_FREQUENCY', 'weekly'),

            // Lista de emails separados por coma. Vacía = usuarios con permiso
            // de reports (helpdesk.metrics.view).
            'recipients' => env('HELPDESK_SCHEDULED_REPORTS_RECIPIENTS', ''),

            // Secciones incluidas en el informe.
            'sections' => [
                'tickets' => env('HELPDESK_SCHEDULED_REPORTS_TICKETS', true),
                'csat' => env('HELPDESK_SCHEDULED_REPORTS_CSAT', true),
                'ops' => env('HELPDESK_SCHEDULED_REPORTS_OPS', true),

                // Comentario en lenguaje natural sobre las cifras del periodo
                // y agrupación por temas de los comentarios de CSAT
                // (TicketInsightsService). Sin agente IA configurado, ambos
                // se omiten y el informe sale solo con los números.
                'insights' => env('HELPDESK_SCHEDULED_REPORTS_INSIGHTS', true),
            ],

            // Adjuntar el CSV de tickets del periodo (TicketsExporter).
            'attach_csv' => env('HELPDESK_SCHEDULED_REPORTS_ATTACH_CSV', true),

            // Límite de filas del CSV adjunto (el mail no es un export masivo).
            'csv_max_rows' => (int) env('HELPDESK_SCHEDULED_REPORTS_CSV_MAX_ROWS', 5000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Observabilidad operativa (helpdesk:ops-metrics, cada 5 min)
    |--------------------------------------------------------------------------
    | El comando programado recolecta profundidad de colas, dead-letters
    | (failed_jobs), webhooks fallidos, breaches SLA de la última hora y
    | tickets sin asignar con SLA próximo; cachea el snapshot (lo muestra el
    | dashboard de reports) y evalúa las alertas de abajo.
    |
    | alerts.enabled: OFF por defecto ("do no harm") — sin activarlo solo se
    | recolecta y loguea, nunca se envía mail. Al activarlo, superar un umbral
    | envía un mail encolado a los usuarios con permiso manage_helpdesk, con
    | cooldown para no repetir en cada run.
    */
    'ops' => [
        // Colas vigiladas (Queue::size). Deben existir en el driver activo.
        'queues' => array_values(array_filter(array_map('trim', explode(
            ',',
            (string) env('HELPDESK_OPS_QUEUES', 'default,helpdesk,helpdesk-ai,helpdesk-webhooks,notifications,emails')
        )))),

        // Ventana "SLA próximo" para tickets sin asignar (minutos).
        'sla_warning_minutes' => (int) env('HELPDESK_OPS_SLA_WARNING_MINUTES', 60),

        'alerts' => [
            'enabled' => env('HELPDESK_OPS_ALERTS_ENABLED', false),

            // Alerta si alguna cola vigilada supera este nº de jobs (0 = off).
            'queue_depth' => (int) env('HELPDESK_OPS_ALERT_QUEUE_DEPTH', 500),

            // Alerta si failed_jobs (dead-letter) supera este valor.
            'failed_jobs' => (int) env('HELPDESK_OPS_ALERT_FAILED_JOBS', 0),

            // Alerta si los breaches SLA de la última hora superan este valor (0 = off).
            'sla_breaches_per_hour' => (int) env('HELPDESK_OPS_ALERT_SLA_BREACHES', 10),

            // Minutos mínimos entre dos mails de alerta.
            'cooldown_minutes' => (int) env('HELPDESK_OPS_ALERT_COOLDOWN', 60),
        ],
    ],
];
