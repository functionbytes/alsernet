<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sending_server_senders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('email');
            $table->string('name')->nullable();
            $table->foreignId('sending_server_id')
                ->constrained('campaign_sending_servers')
                ->cascadeOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['sending_server_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sending_server_senders');
    }
};
