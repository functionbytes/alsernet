<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magic_login_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magic_login_tokens');
    }
};
