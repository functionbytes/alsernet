<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_product_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('rule_type')->nullable();
            $table->boolean('is_returnable')->default(true);
            $table->integer('return_period_days')->nullable();
            $table->decimal('max_return_percentage', 5, 2)->nullable();
            $table->json('conditions')->nullable();
            $table->json('excluded_reasons')->nullable();
            $table->boolean('requires_original_packaging')->default(false);
            $table->boolean('requires_receipt')->default(false);
            $table->boolean('allow_partial_return')->default(true);
            $table->text('special_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->index('category_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_product_rules');
    }
};
