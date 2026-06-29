<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_request_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('review_google_locations')
                ->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('subject', 200);
            $table->text('message');
            $table->string('review_url', 500);
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamps();
        });

        Schema::create('review_request_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained('review_request_campaigns')
                ->cascadeOnDelete();
            $table->string('customer_name', 150);
            $table->string('customer_email', 200);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_request_sends');
        Schema::dropIfExists('review_request_campaigns');
    }
};
