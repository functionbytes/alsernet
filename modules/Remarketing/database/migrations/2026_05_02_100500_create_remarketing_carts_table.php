<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('remarketing_customers')->nullOnDelete();
            $table->string('external_id');
            $table->string('visitor_id', 64)->nullable();
            $table->string('email')->nullable();
            $table->json('items');
            $table->decimal('total', 12, 2);
            $table->char('currency', 3)->default('EUR');
            $table->enum('status', ['active', 'abandoned', 'recovered', 'converted'])->default('active');
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->text('url')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'external_id']);
            $table->index(['store_id', 'status', 'abandoned_at']);
            $table->index('customer_id');
            $table->index('visitor_id');
            $table->index(['email', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_carts');
    }
};
