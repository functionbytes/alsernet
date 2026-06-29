<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Los settings se guardan via helper setting()
        // Esta migración solo documenta los nuevos settings necesarios
        // analytics_reports_daily_enabled (bool)
        // analytics_reports_weekly_enabled (bool)
        // analytics_reports_monthly_enabled (bool)
        // analytics_reports_email (string)
    }

    public function down(): void {}
};
