<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_conversations', 'group_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('assignee_id');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversations', 'group_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
