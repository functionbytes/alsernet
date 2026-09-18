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
        Schema::create('erp_endpoint_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('erp_endpoints')->onDelete('cascade');
            $table->string('token', 64)->unique()->index(); // UUID or hash
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('usage_limit')->nullable(); // Max calls allowed
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('allowed_ips')->nullable(); // IP whitelist
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('endpoint_id');
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_endpoint_tokens');
    }
};
