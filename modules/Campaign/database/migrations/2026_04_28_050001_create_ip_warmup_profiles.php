<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_ip_warmup_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sending_server_id');
            $table->unsignedBigInteger('day_number'); // 1-30
            $table->unsignedBigInteger('daily_limit');
            $table->timestamps();
            $table->unique(['sending_server_id', 'day_number']);
            $table->foreign('sending_server_id')
                ->references('id')
                ->on('campaign_sending_servers')
                ->onDelete('cascade');
        });

        Schema::table('campaign_sending_servers', function (Blueprint $table): void {
            $table->timestamp('warmup_started_at')->nullable()->after('status');
            $table->timestamp('warmup_completed_at')->nullable()->after('warmup_started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_ip_warmup_profiles');
        Schema::table('campaign_sending_servers', function (Blueprint $table): void {
            $table->dropColumn(['warmup_started_at', 'warmup_completed_at']);
        });
    }
};
