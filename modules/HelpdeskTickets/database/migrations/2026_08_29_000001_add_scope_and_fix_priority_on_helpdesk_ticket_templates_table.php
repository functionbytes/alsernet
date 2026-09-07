<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ticket_templates')) {
            return;
        }

        // priority_id apuntaba a helpdesk_priorities (tabla usada por las
        // politicas SLA), pero los tickets reales guardan la prioridad como
        // texto simple (low/normal/high/urgent) en la columna `priority` de
        // helpdesk_tickets — nunca se podia aplicar correctamente. Se
        // reemplaza por una columna `priority` que coincide con el esquema
        // real de tickets.
        Schema::connection('helpdesk')->table('helpdesk_ticket_templates', function (Blueprint $table) {
            if (Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_templates', 'priority_id')) {
                $table->dropForeign(['priority_id']);
                $table->dropColumn('priority_id');
            }
        });

        Schema::connection('helpdesk')->table('helpdesk_ticket_templates', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_templates', 'priority')) {
                $table->string('priority', 20)->nullable()->after('category_id');
            }

            // created_by null = plantilla general (visible/aplicable por
            // cualquiera); con valor = plantilla personal, solo visible para
            // ese usuario. Sin FK: los usuarios viven en la conexion por
            // defecto (mariadb), no en `helpdesk`.
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_templates', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_active')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ticket_templates')) {
            return;
        }

        Schema::connection('helpdesk')->table('helpdesk_ticket_templates', function (Blueprint $table) {
            if (Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_templates', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_templates', 'priority')) {
                $table->dropColumn('priority');
            }
        });

        Schema::connection('helpdesk')->table('helpdesk_ticket_templates', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_templates', 'priority_id')) {
                $table->unsignedBigInteger('priority_id')->nullable();

                $table->foreign('priority_id')
                    ->references('id')
                    ->on('helpdesk_priorities')
                    ->nullOnDelete();
            }
        });
    }
};
