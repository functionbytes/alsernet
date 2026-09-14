<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_chat_flows', function (Blueprint $table) {
            $table->json('edges')->nullable()->change();
        });
    }

    public function down(): void
    {
        // edges was already nullable in the original create migration — no-op rollback needed
    }
};
