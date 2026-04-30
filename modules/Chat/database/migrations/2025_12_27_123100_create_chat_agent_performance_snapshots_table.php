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
        Schema::create('chat_agent_performance_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable()->index('agent_performance_snapshots_team_id_foreign_idx');
            $table->date('snapshot_date')->index('agent_performance_snapshots_snapshot_date_index');
            $table->enum('snapshot_type', ['daily', 'hourly'])->default('daily');
            $table->tinyInteger('snapshot_hour')->nullable();
            $table->json('metrics')->comment('Cached performance metrics: conversations_assigned, conversations_resolved, messages_sent, first_response_time_avg, resolution_time_avg, csat_avg_rating, sla_breaches');
            $table->timestamps();

            $table->index(['account_id', 'snapshot_date'], 'agent_performance_snapshots_account_id_snapshot_date_index');
            $table->index(['user_id', 'snapshot_date'], 'agent_performance_snapshots_user_id_snapshot_date_index');
            $table->unique(['account_id', 'user_id', 'snapshot_date', 'snapshot_type', 'snapshot_hour'], 'agent_snapshot_unique');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('chat_teams')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_agent_performance_snapshots');
    }
};
