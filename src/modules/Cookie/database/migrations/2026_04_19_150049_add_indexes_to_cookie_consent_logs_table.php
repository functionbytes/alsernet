<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cookie_consent_logs', function (Blueprint $table) {
            $table->index('action');
            $table->index(['ip_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('cookie_consent_logs', function (Blueprint $table) {
            $table->dropIndex(['action']);
            $table->dropIndex(['ip_hash', 'created_at']);
        });
    }
};
