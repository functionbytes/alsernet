<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table): void {
            $table->timestamp('snoozed_until')->nullable()->after('last_message_at');
            $table->unsignedBigInteger('snoozed_by')->nullable()->after('snoozed_until');

            $table->index('snoozed_until', 'helpdesk_conversations_snoozed_until_index');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table): void {
            $table->dropIndex('helpdesk_conversations_snoozed_until_index');
            $table->dropColumn(['snoozed_until', 'snoozed_by']);
        });
    }
};
