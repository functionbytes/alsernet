<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->unsignedInteger('total_created')->default(0);
            $table->unsignedInteger('total_closed')->default(0);
            $table->unsignedInteger('total_resolved')->default(0);
            $table->float('avg_response_time_minutes')->nullable();
            $table->float('avg_resolution_time_minutes')->nullable();
            $table->unsignedInteger('sla_breached_count')->default(0);
            $table->json('by_category')->nullable();
            $table->json('by_priority')->nullable();
            $table->json('by_status')->nullable();
            $table->json('agent_performance')->nullable();
            $table->timestamps();

            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_daily_reports');
    }
};
