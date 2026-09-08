<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los paneles de Preguntas y Opiniones filtran por `status` y ordenan por
 * `ps_date` (QuestionsManagerController::index → `latest('ps_date')`), pero el
 * único índice compuesto que existía era `status + created_at`.
 *
 * Resultado medido el 7-sep-2026 con EXPLAIN:
 *
 *   where status='pending' order by ps_date desc limit 25
 *     → type=range, rows=52315, Extra="Using filesort"
 *
 *   order by ps_date desc limit 25   (la pestaña "Todas", sin filtro)
 *     → type=ALL, key=NULL, rows=52315, Extra="Using filesort"
 *
 * Es decir: cada carga del panel ordenaba en memoria las 52.000 preguntas para
 * enseñar 25. Con `(status, ps_date)` el filtro y el orden salen del mismo
 * índice, y con `ps_date` suelto se cubre la pestaña sin filtro.
 *
 * `created_at` se deja como está: lo usan otras consultas y quitarlo no aporta.
 */
return new class extends Migration
{
    private const CONNECTION = 'helpdesk';

    /**
     * @var array<string, array<string, array<int, string>>>
     */
    private const INDEXES = [
        'product_questions' => [
            'product_questions_status_ps_date_index' => ['status', 'ps_date'],
            'product_questions_ps_date_index' => ['ps_date'],
        ],
        'product_reviews' => [
            'product_reviews_status_ps_date_index' => ['status', 'ps_date'],
            'product_reviews_ps_date_index' => ['ps_date'],
        ],
        // El envío de la campaña de cumpleaños filtra por estado para saber a
        // quién le toca; hoy es un full scan de la tabla entera.
        'helpdesk_birthday_recipients' => [
            'helpdesk_birthday_recipients_status_index' => ['status'],
        ],
    ];

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        foreach (self::INDEXES as $tabla => $indices) {
            if (! $schema->hasTable($tabla)) {
                continue;
            }

            $schema->table($tabla, function (Blueprint $table) use ($schema, $tabla, $indices) {
                foreach ($indices as $nombre => $columnas) {
                    if ($schema->hasColumns($tabla, $columnas) && ! $schema->hasIndex($tabla, $nombre)) {
                        $table->index($columnas, $nombre);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        foreach (self::INDEXES as $tabla => $indices) {
            if (! $schema->hasTable($tabla)) {
                continue;
            }

            $schema->table($tabla, function (Blueprint $table) use ($schema, $tabla, $indices) {
                foreach (array_keys($indices) as $nombre) {
                    if ($schema->hasIndex($tabla, $nombre)) {
                        $table->dropIndex($nombre);
                    }
                }
            });
        }
    }
};
