<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Language routing support: stamp the detected language of the first customer
 * message on the ticket so assignment can filter agents by language.
 *
 * Lives in HelpdeskAgents (owner of the AI enrichment jobs) and is guarded
 * with hasColumn so it coexists with any future HelpdeskTickets schema work.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_tickets') && ! $schema->hasColumn('helpdesk_tickets', 'detected_language')) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->string('detected_language', 8)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_tickets') && $schema->hasColumn('helpdesk_tickets', 'detected_language')) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->dropIndex(['detected_language']);
                $table->dropColumn('detected_language');
            });
        }
    }
};
