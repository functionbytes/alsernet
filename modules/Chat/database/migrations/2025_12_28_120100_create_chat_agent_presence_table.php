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
        Schema::create('chat_agent_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chat_accounts')->onDelete('cascade');
            $table->enum('status', ['online', 'offline', 'away', 'busy'])->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('account_id');
            $table->index('status');
            $table->index('last_seen_at');

            // Unique constraint: one presence record per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_agent_presence');
    }
};
