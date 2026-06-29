<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
            $table->fullText(['body'], 'helpdesk_conversation_items_body_fulltext');
        });

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->fullText(['subject'], 'helpdesk_conversations_subject_fulltext');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
            $table->dropFullText('helpdesk_conversation_items_body_fulltext');
        });

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropFullText('helpdesk_conversations_subject_fulltext');
        });
    }
};
