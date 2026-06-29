<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_report_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('name');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index('is_active');
            $table->index('next_run_at');
            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::table('analytics_report_schedules', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['is_active', 'next_run_at']);
            $table->dropIndex(['next_run_at']);
            $table->dropIndex(['is_active']);
            $table->dropColumn('user_id');
        });
    }
};
