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
        Schema::create('chat_customer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('chat_customers')->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->string('session_id')->nullable();
            $table->json('session_data')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['account_id', 'session_token']);
            $table->index(['customer_id', 'active']);
            $table->index('ip_address');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_customer_sessions');
    }
};
