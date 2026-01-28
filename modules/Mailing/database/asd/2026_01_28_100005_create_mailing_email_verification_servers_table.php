<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_email_verification_servers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Server identification
            $table->string('name');
            $table->enum('type', ['zerobounce', 'neverbounce', 'kickbox', 'clearout', 'debounce', 'custom'])->default('zerobounce');
            $table->enum('status', ['active', 'inactive', 'error'])->default('inactive');

            // API configuration
            $table->text('api_key')->nullable(); // Will be encrypted in model
            $table->string('api_url')->nullable();
            $table->json('api_options')->nullable();

            // Verification settings
            $table->boolean('check_disposable')->default(true);
            $table->boolean('check_role_based')->default(true);
            $table->boolean('check_smtp')->default(true);
            $table->boolean('check_catch_all')->default(false);

            // Rate limiting
            $table->integer('requests_per_minute')->default(10);
            $table->integer('requests_per_day')->default(1000);

            // Usage tracking
            $table->integer('verifications_today')->default(0);
            $table->integer('verifications_this_month')->default(0);
            $table->integer('total_verifications')->default(0);

            // Credit management
            $table->integer('credits_available')->nullable();
            $table->integer('credits_used')->default(0);
            $table->integer('credit_threshold')->default(10); // Percentage warning
            $table->timestamp('credits_last_checked_at')->nullable();

            // Statistics
            $table->integer('valid_count')->default(0);
            $table->integer('invalid_count')->default(0);
            $table->integer('risky_count')->default(0);
            $table->integer('unknown_count')->default(0);

            // Monitoring
            $table->timestamp('last_verification_at')->nullable();
            $table->text('last_error')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_email_verification_servers');
    }
};
