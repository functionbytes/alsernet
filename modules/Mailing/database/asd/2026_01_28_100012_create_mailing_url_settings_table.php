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
        Schema::create('mailing_url_settings', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('app_url')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailing_url_settings');
    }
};
