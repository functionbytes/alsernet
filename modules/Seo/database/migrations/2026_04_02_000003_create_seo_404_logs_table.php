<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_404_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500);
            $table->string('referer', 1000)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->unsignedInteger('hit_count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->boolean('has_redirect')->default(false);
            $table->timestamps();

            $table->unique('path');
            $table->index('hit_count');
            $table->index('last_seen_at');
            $table->index('has_redirect');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_404_logs');
    }
};
