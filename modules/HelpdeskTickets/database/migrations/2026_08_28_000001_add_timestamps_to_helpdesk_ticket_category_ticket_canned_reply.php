<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * La tabla se creo sin timestamps (2026_04_20_000024_misc_pivots) pero
     * TicketCategory::ticketCannedReplies() usa ->withTimestamps() — el
     * eager load rompia con "Unknown column ...created_at" en cuanto la
     * pantalla de Categorias recibia trafico real (nunca antes, porque
     * HelpdeskTickets no estaba activado).
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ticket_category_ticket_canned_reply')
            && ! $schema->hasColumn('helpdesk_ticket_category_ticket_canned_reply', 'created_at')) {
            $schema->table('helpdesk_ticket_category_ticket_canned_reply', function (Blueprint $table) {
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ticket_category_ticket_canned_reply')
            && $schema->hasColumn('helpdesk_ticket_category_ticket_canned_reply', 'created_at')) {
            $schema->table('helpdesk_ticket_category_ticket_canned_reply', function (Blueprint $table) {
                $table->dropColumn(['created_at', 'updated_at']);
            });
        }
    }
};
