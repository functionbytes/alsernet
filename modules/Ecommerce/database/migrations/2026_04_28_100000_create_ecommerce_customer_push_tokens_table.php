<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_customer_push_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('ecommerce_customers')->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->enum('platform', ['ios', 'android']);
            $table->string('device_id')->nullable();
            $table->string('app_version', 32)->nullable();
            $table->string('locale', 8)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['customer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_customer_push_tokens');
    }
};
