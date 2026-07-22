<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea helpdesk_ticket_sla_breaches con el modelo TicketSlaBreach.
 *
 * La migración original (2025_12_29_020928) creó sla_type/minutes_over, pero el
 * modelo (y los eventos/notificaciones TicketSlaBreached, listeners de
 * broadcast, SlaService) escriben y leen breach_type, due_at,
 * breach_duration_minutes, resolved, resolved_at y metadata — cualquier
 * registro de un incumplimiento de SLA reventaba con "Unknown column".
 *
 * Idempotente (hasColumn) para poder ejecutarse sobre BDs que ya fueron
 * parcheadas a mano. Se conservan sla_type/minutes_over como legacy (ahora
 * nullable) por si hubiera datos históricos.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('helpdesk_ticket_sla_breaches', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_ticket_sla_breaches', 'breach_type')) {
                $table->string('breach_type')->nullable()->after('ticket_id');
            }
            if (! $schema->hasColumn('helpdesk_ticket_sla_breaches', 'due_at')) {
                $table->timestamp('due_at')->nullable()->after('breach_type');
            }
            if (! $schema->hasColumn('helpdesk_ticket_sla_breaches', 'breach_duration_minutes')) {
                $table->integer('breach_duration_minutes')->nullable()->after('breached_at');
            }
            if (! $schema->hasColumn('helpdesk_ticket_sla_breaches', 'resolved')) {
                $table->boolean('resolved')->default(false)->after('breach_duration_minutes');
            }
            if (! $schema->hasColumn('helpdesk_ticket_sla_breaches', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolved');
            }
            if (! $schema->hasColumn('helpdesk_ticket_sla_breaches', 'metadata')) {
                $table->json('metadata')->nullable()->after('resolved_at');
            }
        });

        // El modelo ya no escribe las columnas legacy: que no bloqueen inserts.
        if ($schema->hasColumn('helpdesk_ticket_sla_breaches', 'sla_type')) {
            DB::connection($this->connection)
                ->statement('ALTER TABLE `helpdesk_ticket_sla_breaches` MODIFY `sla_type` varchar(191) NULL');

            // Backfill del nuevo nombre desde los datos históricos.
            DB::connection($this->connection)
                ->statement('UPDATE `helpdesk_ticket_sla_breaches` SET `breach_type` = `sla_type` WHERE `breach_type` IS NULL AND `sla_type` IS NOT NULL');
        }

        if ($schema->hasColumn('helpdesk_ticket_sla_breaches', 'minutes_over')) {
            DB::connection($this->connection)
                ->statement('UPDATE `helpdesk_ticket_sla_breaches` SET `breach_duration_minutes` = `minutes_over` WHERE `breach_duration_minutes` IS NULL AND `minutes_over` IS NOT NULL');
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('helpdesk_ticket_sla_breaches', function (Blueprint $table) use ($schema) {
            foreach (['breach_type', 'due_at', 'breach_duration_minutes', 'resolved', 'resolved_at', 'metadata'] as $column) {
                if ($schema->hasColumn('helpdesk_ticket_sla_breaches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
