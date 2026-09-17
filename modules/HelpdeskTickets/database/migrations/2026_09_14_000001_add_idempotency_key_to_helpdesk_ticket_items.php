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

        if (! $schema->hasTable('helpdesk_ticket_items') || $schema->hasColumn('helpdesk_ticket_items', 'idempotency_key')) {
            return;
        }

        $schema->table('helpdesk_ticket_items', function (Blueprint $table): void {
            $table->string('idempotency_key', 120)->nullable()->after('metadata');
            $table->unique(['ticket_id', 'idempotency_key'], 'ticket_items_ticket_id_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ticket_items') || ! $schema->hasColumn('helpdesk_ticket_items', 'idempotency_key')) {
            return;
        }

        $schema->table('helpdesk_ticket_items', function (Blueprint $table): void {
            $table->dropUnique('ticket_items_ticket_id_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
