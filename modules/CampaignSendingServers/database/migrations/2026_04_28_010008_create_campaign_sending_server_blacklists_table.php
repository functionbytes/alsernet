<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sending_server_blacklists', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason')->nullable();
            $table->string('source', 32)->default('manual')->index(); // manual|bounce|feedback|import
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sending_server_blacklists');
    }
};
