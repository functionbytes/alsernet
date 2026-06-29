<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('discount_type', 20); // percentage, fixed
            $table->decimal('discount_value', 10, 2);
            $table->string('image')->nullable();
            $table->string('status', 20)->default('published');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_bundles');
    }
};
