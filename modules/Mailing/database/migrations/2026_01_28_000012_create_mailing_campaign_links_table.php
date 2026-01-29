<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_campaign_links', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('campaign_id');
            $table->text('url');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('campaign_id')->references('id')->on('mailing_campaigns')->onDelete('cascade');

            $table->index('campaign_id');

            // Foreign Keys
            $table->foreign('campaign_id')
                ->references('id')
                ->on('mailing_campaigns')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_campaign_links');
    }
};
