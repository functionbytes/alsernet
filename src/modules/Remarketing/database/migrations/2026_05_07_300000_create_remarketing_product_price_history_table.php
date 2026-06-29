<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_product_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->unsignedBigInteger('external_product_id');
            $table->decimal('price', 12, 4);
            $table->decimal('previous_price', 12, 4)->nullable();
            $table->decimal('drop_percent', 5, 2)->nullable();
            $table->timestamp('recorded_at');

            $table->index(['store_id', 'external_product_id', 'recorded_at'], 'rmkt_pph_store_product_recorded_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_product_price_history');
    }
};
