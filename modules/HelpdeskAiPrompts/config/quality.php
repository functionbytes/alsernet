<?php

return [
    /*
     | Regresión con conversaciones reales: re-ejecuta las últimas preguntas
     | reales de un caso contra su versión actual (o un borrador) y compara.
     */
    'regression' => [
        // Tope duro de preguntas por ejecución (coste): nunca se supera.
        'max_questions' => (int) env('AI_REGRESSION_MAX_QUESTIONS', 20),
        'default_questions' => (int) env('AI_REGRESSION_DEFAULT_QUESTIONS', 10),
        // Tope de coste estimado (€) por ejecución, juez incluido.
        'max_cost_eur' => (float) env('AI_REGRESSION_MAX_COST_EUR', 1.0),
        'judge_enabled' => (bool) env('AI_REGRESSION_JUDGE_ENABLED', true),
        'judge_model' => env('AI_REGRESSION_JUDGE_MODEL', 'gpt-4o-mini'),
        // Puntuación del juez igual o inferior a la cual la pregunta cuenta como regresión.
        'regression_score' => 2,
    ],

    /*
     | Alertas por caso (comando ai-prompts:quality-alerts, cada hora).
     */
    'alerts' => [
        'window_hours' => (int) env('AI_ALERTS_WINDOW_HOURS', 24),
        // Mismo aviso (caso + tipo) no se repite antes de estas horas.
        'cooldown_hours' => (int) env('AI_ALERTS_COOLDOWN_HOURS', 24),
        'escalation_pct' => (float) env('AI_ALERTS_ESCALATION_PCT', 40),
        // Mínimo de respuestas en la ventana para evaluar la escalada (evita 1 de 1 = 100 %).
        'escalation_min_runs' => (int) env('AI_ALERTS_ESCALATION_MIN_RUNS', 5),
        'dislike_pct' => (float) env('AI_ALERTS_DISLIKE_PCT', 30),
        'dislike_min_ratings' => (int) env('AI_ALERTS_DISLIKE_MIN_RATINGS', 5),
        'cost_eur_day' => (float) env('AI_ALERTS_COST_EUR_DAY', 5),
        // Además de la notificación en el panel, enviar email.
        'mail' => (bool) env('AI_ALERTS_MAIL', false),
    ],
];
