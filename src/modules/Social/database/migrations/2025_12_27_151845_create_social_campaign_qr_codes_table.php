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
        Schema::create('social_campaign_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('social_campaigns')->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->string('url', 500);
            $table->string('file_path')->nullable();
            $table->integer('scans_count')->default(0);
            $table->timestamp('last_scanned_at')->nullable();
            $table->json('scan_data')->nullable(); // Geolocation, device, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_campaign_qr_codes');
    }
};
