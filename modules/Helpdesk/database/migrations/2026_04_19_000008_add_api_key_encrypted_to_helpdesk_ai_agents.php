<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_ai_agents', function (Blueprint $table) {
            $table->text('api_key_encrypted')->nullable()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_ai_agents', function (Blueprint $table) {
            $table->dropColumn('api_key_encrypted');
        });
    }
};
