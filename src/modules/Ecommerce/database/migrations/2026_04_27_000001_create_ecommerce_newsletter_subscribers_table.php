<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('name', 255)->nullable();
            $table->string('status', 20)->default('subscribed');
            $table->string('source', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_token', 80)->unique()->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_newsletter_subscribers');
    }
};
