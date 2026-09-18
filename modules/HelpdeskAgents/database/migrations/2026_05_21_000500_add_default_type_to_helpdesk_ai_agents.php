<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_ai_agents')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ai_agents', function (Blueprint $table) {
            $table->string('type')->default('chat')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_ai_agents')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ai_agents', function (Blueprint $table) {
            $table->string('type')->change();
        });
    }
};
