<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('assignee_id');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
