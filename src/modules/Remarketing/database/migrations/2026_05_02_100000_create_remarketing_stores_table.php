<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('name');
            $table->string('domain');
            $table->text('access_token')->nullable();
            $table->string('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('webhook_token', 64)->unique();
            $table->enum('status', ['pending', 'active', 'error', 'paused'])->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index(['platform', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_stores');
    }
};
