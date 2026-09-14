<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // push_notification_tokens
        Schema::create('push_notification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token');
            $table->string('device_type')->nullable();
            $table->string('device_id')->nullable();
            $table->boolean('active')->default(true);
            $table->datetime('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'token'], 'idx_10310');
        });

        // notification_settings
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('channel');
            $table->string('notification_type');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'channel', 'notification_type'], 'idx_47991');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('push_notification_tokens');
    }
};
