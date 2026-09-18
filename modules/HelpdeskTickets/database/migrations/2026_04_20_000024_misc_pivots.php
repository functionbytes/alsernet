<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ticket_category_ticket_canned_reply')) {
            $schema->create('helpdesk_ticket_category_ticket_canned_reply', function (Blueprint $t) {
                $t->unsignedBigInteger('ticket_category_id');
                $t->unsignedBigInteger('ticket_canned_reply_id');
                $t->primary(['ticket_category_id', 'ticket_canned_reply_id'], 'hdtccr_pk');
            });
        }

        // TicketGroup needs is_default
        if ($schema->hasTable('helpdesk_ticket_groups') && ! $schema->hasColumn('helpdesk_ticket_groups', 'is_default')) {
            $schema->table('helpdesk_ticket_groups', fn (Blueprint $t) => $t->boolean('is_default')->default(false)->index());
        }
    }

    public function down(): void {}
};
