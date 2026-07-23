<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snooze de tickets (posponer): el agente aparta un ticket hasta una fecha; se
 * oculta de las colas activas y reaparece cuando vence. Paridad con la feature
 * ya existente en conversaciones (snoozed_until/snoozed_by) y con Zendesk/Front.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'snoozed_until')) {
                $table->timestamp('snoozed_until')->nullable()->after('is_archived');
                $table->unsignedBigInteger('snoozed_by')->nullable()->after('snoozed_until');
                $table->index('snoozed_until', 'helpdesk_tickets_snoozed_until_index');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            if (Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'snoozed_until')) {
                $table->dropIndex('helpdesk_tickets_snoozed_until_index');
                $table->dropColumn(['snoozed_until', 'snoozed_by']);
            }
        });
    }
};
