<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sending_server_domains', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->foreignId('sending_server_id')
                ->nullable()
                ->constrained('campaign_sending_servers')
                ->nullOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->boolean('signing_enabled')->default(false);
            $table->string('dkim_selector')->nullable();
            $table->text('dkim_public_key')->nullable();
            $table->text('dkim_private_key')->nullable(); // encrypted
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sending_server_domains');
    }
};
