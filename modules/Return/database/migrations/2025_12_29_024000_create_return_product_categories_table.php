<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('allow_returns')->default(true);
            $table->integer('default_return_days')->default(30);
            $table->text('return_policy_text')->nullable();
            $table->json('return_restrictions')->nullable();
            $table->timestamps();
            $table->index('allow_returns');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_product_categories');
    }
};
