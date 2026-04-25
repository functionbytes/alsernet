<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_payment_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('ecommerce_payments')->nullOnDelete();
            $table->string('payment_method', 60)->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_payment_logs');
    }
};
