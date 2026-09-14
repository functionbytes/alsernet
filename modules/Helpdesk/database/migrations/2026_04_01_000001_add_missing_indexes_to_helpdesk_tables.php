<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table): void {
            $table->index('user_id');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['author_id']);
        });
    }
};
