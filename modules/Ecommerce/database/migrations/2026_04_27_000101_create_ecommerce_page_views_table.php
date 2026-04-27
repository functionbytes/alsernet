<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_page_views', function (Blueprint $table): void {
            $table->id();
            $table->string('session_id', 80);
            $table->foreignId('customer_id')->nullable()->constrained('ecommerce_customers')->nullOnDelete();
            $table->string('url', 500);
            $table->string('referrer', 500)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('ecommerce_products')->nullOnDelete();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('viewed_at');

            $table->index(['session_id', 'viewed_at']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_page_views');
    }
};
