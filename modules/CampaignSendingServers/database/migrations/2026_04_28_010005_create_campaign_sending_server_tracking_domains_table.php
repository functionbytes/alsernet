<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sending_server_tracking_domains', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name')->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->string('verification_method', 16)->default('cname'); // cname|host|caddy
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sending_server_tracking_domains');
    }
};
