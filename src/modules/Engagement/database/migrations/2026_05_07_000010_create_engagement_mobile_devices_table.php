<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->create('engagement_mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbox_id')->constrained('helpdesk_inboxes')->cascadeOnDelete();
            $table->string('device_token', 255)->index();
            $table->string('platform', 20)->index(); // ios, android
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->string('locale')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('push_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['inbox_id', 'device_token']);
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('engagement_mobile_devices');
    }
};
