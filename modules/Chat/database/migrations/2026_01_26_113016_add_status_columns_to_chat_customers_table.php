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
        Schema::table('chat_customers', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('custom_attributes');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
            $table->boolean('is_active')->default(true)->after('banned_at');
            $table->timestamp('email_verified_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_customers', function (Blueprint $table) {
            $table->dropColumn('is_banned');
            $table->dropColumn('banned_at');
            $table->dropColumn('is_active');
            $table->dropColumn('email_verified_at');
            $table->dropSoftDeletes();
        });
    }
};
