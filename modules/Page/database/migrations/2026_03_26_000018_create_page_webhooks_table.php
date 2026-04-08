<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('secret', 64)->nullable();
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->integer('timeout')->default(10);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('fail_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_webhooks');
    }
};
