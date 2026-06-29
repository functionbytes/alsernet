<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impersonator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('impersonated_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->index(['impersonator_id', 'started_at']);
            $table->index(['impersonated_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
