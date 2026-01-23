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
        Schema::create('webhook_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')
                ->constrained('webhook_deliveries')
                ->onDelete('cascade');

            $table->json('request_headers')->nullable()->comment('Sanitized headers');
            $table->json('request_body')->nullable()->comment('Sanitized body');

            $table->integer('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('response_body')->nullable()->comment('Truncated to 5000 chars');

            $table->integer('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('delivery_id', 'idx_delivery_logs_delivery');
            $table->index('created_at', 'idx_delivery_logs_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_delivery_logs');
    }
};
