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
        // Table likely already exists from other modules
        // This is a safety check - if it doesn't exist, create it
        // Otherwise, skip silently
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique()->nullable();
                $table->string('sku')->unique()->nullable();
                $table->string('title')->nullable();
                $table->string('slug')->unique()->nullable();
                $table->string('reference')->nullable();
                $table->string('barcode')->nullable();
                $table->integer('stock')->default(0)->nullable();
                $table->boolean('available')->default(true)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
