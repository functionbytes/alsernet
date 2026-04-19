<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_ticket_categories', function (Blueprint $table) {
            $table->string('uid')->nullable()->unique()->after('id');
            $table->boolean('is_active')->default(true)->after('color');
            $table->integer('position')->default(0)->after('is_active');
        });

        Schema::table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->string('uid')->nullable()->unique()->after('id');
            $table->integer('position')->default(0)->after('order');
        });

        Schema::table('helpdesk_groups', function (Blueprint $table) {
            $table->string('uid')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_ticket_categories', function (Blueprint $table) {
            $table->dropColumn(['uid', 'is_active', 'position']);
        });

        Schema::table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->dropColumn(['uid', 'position']);
        });

        Schema::table('helpdesk_groups', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
