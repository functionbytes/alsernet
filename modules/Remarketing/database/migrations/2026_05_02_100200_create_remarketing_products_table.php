<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->string('external_id');
            $table->string('sku', 100)->nullable();
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->text('url');
            $table->text('image_url')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->integer('inventory')->default(0);
            $table->string('vendor')->nullable();
            $table->json('tags')->nullable();
            $table->json('collections')->nullable();
            $table->enum('status', ['active', 'archived', 'draft'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'external_id']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'inventory']);
            $table->index(['store_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_products');
    }
};
