<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->boolean('is_spam')->default(false)->after('is_archived');
            $table->index('is_spam', 'helpdesk_conversations_is_spam_index');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropIndex('helpdesk_conversations_is_spam_index');
            $table->dropColumn('is_spam');
        });
    }
};
