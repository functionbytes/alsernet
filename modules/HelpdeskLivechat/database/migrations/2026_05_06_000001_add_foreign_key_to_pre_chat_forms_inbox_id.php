<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_pre_chat_forms', function (Blueprint $table) {
            $table->foreign('inbox_id')
                ->references('id')
                ->on('helpdesk_inboxes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_pre_chat_forms', function (Blueprint $table) {
            $table->dropForeign(['inbox_id']);
        });
    }
};
