<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_cronjob_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Job identification
            $table->string('job_name');
            $table->string('schedule');
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active');

            // Timing
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();

            // Artisan command details
            $table->string('command');

            // Job configuration
            $table->json('settings')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('job_name');
            $table->index('next_run_at');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_cronjob_settings');
    }
};
