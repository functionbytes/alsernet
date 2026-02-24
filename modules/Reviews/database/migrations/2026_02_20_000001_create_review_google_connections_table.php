<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_google_connections', function (Blueprint $table) {
            $table->id();

            // User who owns this connection
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who connected this Google account');

            // Connection details
            $table->string('name', 100)->comment('Friendly name for this connection');
            $table->string('google_account_id', 100)->unique()->comment('Google account identifier');
            $table->string('google_email', 255)->comment('Google account email');

            // OAuth tokens (encrypted at model level)
            $table->text('access_token')->comment('Encrypted OAuth access token');
            $table->text('refresh_token')->comment('Encrypted OAuth refresh token');
            $table->timestamp('token_expires_at')->nullable()->comment('When access token expires');
            $table->json('scopes')->nullable()->comment('OAuth scopes granted');

            // Status tracking
            $table->enum('status', ['pending', 'active', 'expired', 'revoked', 'error'])
                ->default('pending')
                ->comment('Connection status');
            $table->text('last_error')->nullable()->comment('Last error message if any');

            // Timestamps
            $table->timestamp('connected_at')->nullable()->comment('When successfully connected');
            $table->timestamp('revoked_at')->nullable()->comment('When user revoked access');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('google_account_id');
            $table->index('status');
            $table->index('connected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_google_connections');
    }
};
