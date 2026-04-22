<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_categories', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('name');
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('name');
            $table->boolean('is_closed')->default(false)->after('is_open');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_categories', function (Blueprint $table) {
            $table->dropColumn('key');
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->dropColumn(['key', 'is_closed']);
        });
    }
};
