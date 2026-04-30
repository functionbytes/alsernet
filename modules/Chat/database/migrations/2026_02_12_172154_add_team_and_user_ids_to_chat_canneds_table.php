<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_canneds', function (Blueprint $table) {
            // JSON columns to store selected team IDs and user IDs
            $table->json('team_ids')->nullable()->after('visibility');
            $table->json('user_ids')->nullable()->after('team_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_canneds', function (Blueprint $table) {
            $table->dropColumn(['team_ids', 'user_ids']);
        });
    }
};
