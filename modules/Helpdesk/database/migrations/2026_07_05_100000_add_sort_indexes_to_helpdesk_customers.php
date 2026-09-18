<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ContactsController::index() ordena por last_seen_at (default) y
 * total_conversations; reports() ordena por total_conversations. Sin
 * indice, EXPLAIN confirma un full table scan (type: ALL, Using filesort).
 * email_verified_at ya tiene indice propio (helpdesk_customers_email_verified_at_index).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_customers', function (Blueprint $table) {
            $table->index('last_seen_at', 'helpdesk_customers_last_seen_at_index');
            $table->index('total_conversations', 'helpdesk_customers_total_conversations_index');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_customers', function (Blueprint $table) {
            $table->dropIndex('helpdesk_customers_last_seen_at_index');
            $table->dropIndex('helpdesk_customers_total_conversations_index');
        });
    }
};
