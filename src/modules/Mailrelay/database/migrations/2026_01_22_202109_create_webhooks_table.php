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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('method')->default('POST');
            $table->json('events')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('auth_type')->default('none');
            $table->json('auth_config')->nullable();
            $table->integer('timeout')->default(30);
            $table->integer('max_retries')->default(3);
            $table->boolean('verify_ssl')->default(true);
            $table->string('secret')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('last_status_code')->nullable();
            $table->integer('last_response_time')->nullable();
            $table->integer('total_calls')->default(0);
            $table->integer('failed_calls')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('last_triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
