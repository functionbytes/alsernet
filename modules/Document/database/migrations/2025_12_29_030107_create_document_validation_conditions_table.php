<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_validation_conditions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('key')->unique();
            $table->string('condition_type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('sale_types')->nullable();
            $table->string('model_field')->nullable();
            $table->json('expected_value')->nullable();
            $table->text('validation_expression')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('key');
            $table->index('condition_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_validation_conditions');
    }
};
