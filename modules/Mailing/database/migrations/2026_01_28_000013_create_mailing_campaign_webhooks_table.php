<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_campaign_webhooks', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->char('uid', 36)->unique();
            $table->string('type');
            $table->text('endpoint');
            $table->unsignedInteger('campaign_id');
            $table->unsignedInteger('campaign_link_id')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('campaign_id')->references('id')->on('mailing_campaigns')->onDelete('cascade');
            $table->foreign('campaign_link_id')->references('id')->on('mailing_campaign_links')->onDelete('set null');

            $table->index('campaign_id');
            $table->index('campaign_link_id');

            // Foreign Keys
            $table->foreign('campaign_id')
                ->references('id')
                ->on('mailing_campaigns')
                ->onDelete('cascade');
            $table->foreign('campaign_link_id')
                ->references('id')
                ->on('mailing_campaign_links')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_campaign_webhooks');
    }
};
