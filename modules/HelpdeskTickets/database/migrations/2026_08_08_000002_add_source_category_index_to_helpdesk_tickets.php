<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modules\Forms\Http\Controllers\Managers\FormsReportController agrupa
 * tickets por category_id filtrando WHERE source = 'formulario' (y una
 * segunda pasada añadiendo status_id IN (...) para el conteo de abiertos).
 * El único índice existente sobre category_id (tickets_category_id_index,
 * ver 2026_05_12_000002_helpdesk_tickets_add_indexes.php) no cubre `source`,
 * así que MySQL no puede filtrar por ambas columnas en el mismo índice.
 *
 * Se añade proactivamente en vez de esperar a que el volumen de tickets lo
 * haga notar -- ver auditoría del módulo Forms del proyecto "system":
 * ese módulo tuvo que añadir índices de rendimiento a posteriori en 4
 * migraciones distintas (2026_03_26, 2026_04_02, 2026_04_19...) porque el
 * inbox/analytics no se pensó con índices compuestos desde el principio.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_tickets')) {
            return;
        }

        if (
            $schema->hasColumn('helpdesk_tickets', 'source') &&
            $schema->hasColumn('helpdesk_tickets', 'category_id') &&
            ! $this->indexExists('helpdesk_tickets', 'tickets_source_category_idx')
        ) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->index(['source', 'category_id'], 'tickets_source_category_idx');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_tickets')) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                try {
                    $table->dropIndex('tickets_source_category_idx');
                } catch (Throwable) {
                    // Index did not exist — safe to ignore
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(
            DB::connection($this->connection)
                ->select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName])
        )->isNotEmpty();
    }
};
