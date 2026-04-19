<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_ticket_categories', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('name');
        });

        Schema::table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('name');
            $table->boolean('is_closed')->default(false)->after('is_open');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_ticket_categories', function (Blueprint $table) {
            $table->dropColumn('key');
        });

        Schema::table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->dropColumn(['key', 'is_closed']);
        });
    }
};
