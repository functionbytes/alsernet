<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_campaign_impressions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('status')->default('shown');
            $table->string('impression_id')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('helpdesk_campaigns')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('helpdesk_customers')->onDelete('set null');

            $table->index('campaign_id');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_campaign_impressions');
    }
};
