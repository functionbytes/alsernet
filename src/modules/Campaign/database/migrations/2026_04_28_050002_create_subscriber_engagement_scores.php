<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_subscriber_engagement_scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subscriber_id');
            $table->integer('score')->default(0); // -100 a +100
            $table->unsignedBigInteger('opens_30d')->default(0);
            $table->unsignedBigInteger('clicks_30d')->default(0);
            $table->unsignedBigInteger('sent_30d')->default(0);
            $table->unsignedBigInteger('bounces_90d')->default(0);
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
            $table->unique('subscriber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_subscriber_engagement_scores');
    }
};
