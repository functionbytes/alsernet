<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Reconcile singular legacy table name `helpdesk_ticket_history` with the
     * plural Eloquent convention `helpdesk_ticket_histories` that the
     * TicketHistory model already expects.
     */
    public function up(): void
    {
        $hasSingular = Schema::connection($this->connection)->hasTable('helpdesk_ticket_history');
        $hasPlural = Schema::connection($this->connection)->hasTable('helpdesk_ticket_histories');

        if ($hasSingular && ! $hasPlural) {
            Schema::connection($this->connection)->rename('helpdesk_ticket_history', 'helpdesk_ticket_histories');
        }
    }

    public function down(): void
    {
        $hasSingular = Schema::connection($this->connection)->hasTable('helpdesk_ticket_history');
        $hasPlural = Schema::connection($this->connection)->hasTable('helpdesk_ticket_histories');

        if ($hasPlural && ! $hasSingular) {
            Schema::connection($this->connection)->rename('helpdesk_ticket_histories', 'helpdesk_ticket_history');
        }
    }
};
