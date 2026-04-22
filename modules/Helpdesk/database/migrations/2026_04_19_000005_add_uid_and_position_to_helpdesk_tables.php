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
            $table->string('uid')->nullable()->unique()->after('id');
            $table->boolean('is_active')->default(true)->after('color');
            $table->integer('position')->default(0)->after('is_active');
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->string('uid')->nullable()->unique()->after('id');
            $table->integer('position')->default(0)->after('order');
        });

        Schema::connection($this->connection)->table('helpdesk_groups', function (Blueprint $table) {
            $table->string('uid')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_categories', function (Blueprint $table) {
            $table->dropColumn(['uid', 'is_active', 'position']);
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->dropColumn(['uid', 'position']);
        });

        Schema::connection($this->connection)->table('helpdesk_groups', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
