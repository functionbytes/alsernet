<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_social_accounts', function (Blueprint $table) {
            $table->unsignedSmallInteger('consecutive_failures')->default(0)->after('last_error_message');
            $table->boolean('circuit_breaker_active')->default(false)->after('consecutive_failures');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_social_accounts', function (Blueprint $table) {
            $table->dropColumn('circuit_breaker_active');
            $table->dropColumn('consecutive_failures');
        });
    }
};
