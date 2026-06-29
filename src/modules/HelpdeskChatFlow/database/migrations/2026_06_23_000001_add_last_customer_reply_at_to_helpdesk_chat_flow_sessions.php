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

        if ($schema->hasColumn('helpdesk_chat_flow_sessions', 'last_customer_reply_at')) {
            return;
        }

        $schema->table('helpdesk_chat_flow_sessions', function (Blueprint $table) {
            // Inactivity threshold based on the customer's last reply, not updated_at
            // (which context writes bump, so noisy flows would never expire).
            $table->timestamp('last_customer_reply_at')->nullable()->after('ended_at');
            $table->index(['status', 'last_customer_reply_at']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_chat_flow_sessions', function (Blueprint $table) {
            $table->dropIndex(['status', 'last_customer_reply_at']);
            $table->dropIndex(['started_at']);
            $table->dropColumn('last_customer_reply_at');
        });
    }
};
