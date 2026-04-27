<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('ecommerce_customers')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('query', 255)->nullable();
            $table->json('filters');
            $table->timestamp('last_notified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_saved_searches');
    }
};
