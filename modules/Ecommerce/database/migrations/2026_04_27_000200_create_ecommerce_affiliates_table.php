<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('ecommerce_customers')->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->decimal('commission_rate', 5, 2);
            $table->string('status', 20)->default('active');
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->timestamps();

            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_affiliates');
    }
};
