<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte de "papelera de registros" (30 días de recuperación antes del
 * borrado definitivo, ver PruneEmailLogsCommand::pruneTrash()): destroy()/
 * bulkDestroy() en EmailLogController pasan de DELETE físico a SoftDeletes
 * (ver EmailLog::class), así que un borrado desde el listado deja de ser
 * irreversible al instante — el registro sigue existiendo (y es
 * recuperable vía restore()) hasta que la purga de la papelera lo elimine
 * de verdad.
 *
 * Indexado (igual que delivered_at): PruneEmailLogsCommand::pruneTrash() y
 * EmailLogController::trash() filtran por esta columna en cada ejecución.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('email_logs', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};
