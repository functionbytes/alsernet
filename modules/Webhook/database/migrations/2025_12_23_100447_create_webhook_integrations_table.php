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
        Schema::create('webhook_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique()->comment('ULID único');
            $table->string('name', 100);
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active');
            $table->string('plan', 50)->default('free')->comment('free, pro, enterprise');
            $table->integer('daily_limit')->default(1000);
            $table->json('allowed_ips')->nullable()->comment('IPs permitidas (opcional)');
            $table->json('allowed_domains')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_integrations_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_integrations');
    }
};
