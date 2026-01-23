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
        Schema::create('webhook_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')
                ->constrained('webhook_integrations')
                ->onDelete('cascade');

            $table->string('key', 64)->unique()->comment('Public key (API_KEY_xxx)');
            $table->string('secret', 255)->comment('Hashed secret (HMAC key)');
            $table->string('name', 100)->nullable()->comment('Key label');
            $table->json('permissions')->nullable()->comment('inbound, outbound, admin');
            $table->integer('rate_limit_per_minute')->default(60);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('integration_id', 'idx_api_keys_integration');
            $table->index('key', 'idx_api_keys_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_api_keys');
    }
};
