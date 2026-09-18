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

        if ($schema->hasTable('helpdesk_conversation_tags') && ! $schema->hasColumn('helpdesk_conversation_tags', 'is_active')) {
            $schema->table('helpdesk_conversation_tags', fn (Blueprint $t) => $t->boolean('is_active')->default(true)->index());
        }

        // `order` column for TicketGroup ordered() scope
        if ($schema->hasTable('helpdesk_ticket_groups') && ! $schema->hasColumn('helpdesk_ticket_groups', 'order')) {
            $schema->table('helpdesk_ticket_groups', fn (Blueprint $t) => $t->integer('order')->default(0)->index());
        }
    }

    public function down(): void {}
};
