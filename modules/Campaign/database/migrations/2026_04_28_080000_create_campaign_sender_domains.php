<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sender_domains', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('domain')->unique();
            $table->boolean('spf_valid')->default(false);
            $table->boolean('dmarc_valid')->default(false);
            $table->boolean('dkim_valid')->default(false);
            $table->boolean('mx_valid')->default(false);
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('status', 16)->default('pending'); // pending|verified|failed
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sender_domains');
    }
};
