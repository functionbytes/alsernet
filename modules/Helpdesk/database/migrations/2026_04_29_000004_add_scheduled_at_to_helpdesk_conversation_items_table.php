<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversation_items', function (Blueprint $table): void {
            $table->timestamp('scheduled_at')->nullable()->after('metadata');
            $table->unsignedBigInteger('scheduled_by')->nullable()->after('scheduled_at');

            $table->index('scheduled_at', 'hci_scheduled_at_index');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversation_items', function (Blueprint $table): void {
            $table->dropIndex('hci_scheduled_at_index');
            $table->dropColumn(['scheduled_at', 'scheduled_by']);
        });
    }
};
