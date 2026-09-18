<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SLA por canal: una política puede quedar acotada a un canal de origen del
 * ticket (tickets.source: widget, email, web_form, portal, api...). NULL =
 * política genérica (aplica a cualquier canal). La selección al crear el
 * ticket prioriza la política con canal coincidente y cae a la genérica
 * (is_default) — ver TicketSlaPolicy::resolveForChannel().
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_sla_policies', function (Blueprint $table) {
            $table->string('channel', 50)->nullable()->after('description');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_sla_policies', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropColumn('channel');
        });
    }
};
