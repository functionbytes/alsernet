<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_product_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('remarketing_customers')->cascadeOnDelete();
            $table->unsignedBigInteger('external_product_id');
            $table->string('source', 32); // 'cart' | 'wishlist' | 'viewed'
            $table->decimal('reference_price', 12, 4)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['store_id', 'customer_id', 'external_product_id', 'source'],
                'uq_watch'
            );
            $table->index(['external_product_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_product_watches');
    }
};
