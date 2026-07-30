<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_conversations', 'inbox_id')) {
                $table->foreignId('inbox_id')->nullable()->after('group_id')
                    ->constrained('helpdesk_inboxes')->nullOnDelete();
                $table->index(['inbox_id', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table) {
            if (Schema::connection('helpdesk')->hasColumn('helpdesk_conversations', 'inbox_id')) {
                $table->dropConstrainedForeignId('inbox_id');
            }
        });
    }
};
