<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Políticas de SLA por condición (24-sep-2026): además del canal, una
 * política puede limitarse a una prioridad y/o a clientes VIP. NULL = da
 * igual (comportamiento de siempre).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('helpdesk_ticket_sla_policies', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_ticket_sla_policies', 'applies_to_priority')) {
                $table->string('applies_to_priority', 10)->nullable()->after('channel');
            }
            if (! $schema->hasColumn('helpdesk_ticket_sla_policies', 'applies_to_vip')) {
                $table->boolean('applies_to_vip')->nullable()->after('applies_to_priority');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_sla_policies', function (Blueprint $table) {
            $table->dropColumn(['applies_to_priority', 'applies_to_vip']);
        });
    }
};
