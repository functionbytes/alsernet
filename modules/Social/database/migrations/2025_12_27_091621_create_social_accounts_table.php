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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();

            // Network and Type
            $table->string('network'); // facebook, instagram, linkedin, twitter, tiktok
            $table->string('channel_type')->nullable(); // page, profile, group

            // Account Info
            $table->string('username');
            $table->string('name');
            $table->string('avatar')->nullable();

            // Authentication
            $table->text('access_token')->nullable(); // Encrypted
            $table->timestamp('token_expiry')->nullable();
            $table->text('refresh_token')->nullable(); // Encrypted

            // Additional Data
            $table->json('data')->nullable(); // Network-specific data

            // Status
            $table->tinyInteger('status')->default(1); // 0=disabled, 1=active, 2=paused, 3=error
            $table->text('last_error')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['account_id', 'network', 'status']);
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
