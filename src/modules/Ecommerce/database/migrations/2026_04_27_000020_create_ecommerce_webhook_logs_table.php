<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 80);
            $table->string('url', 500);
            $table->json('payload');
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempts')->default(0);
            $table->boolean('success')->default(false);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['event', 'success']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_webhook_logs');
    }
};
