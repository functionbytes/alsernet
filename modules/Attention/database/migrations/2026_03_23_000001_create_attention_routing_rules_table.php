<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attention_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('priority')->default(10);

            // Conditions (null = matches any value)
            $table->unsignedBigInteger('condition_type_id')->nullable();
            $table->unsignedBigInteger('condition_category_id')->nullable();
            $table->unsignedBigInteger('condition_sede_id')->nullable();

            // Actions
            $table->unsignedBigInteger('assign_to_user_id')->nullable();
            $table->unsignedBigInteger('assign_to_department_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'priority']);
            $table->foreign('condition_type_id')->references('id')->on('attention_types')->nullOnDelete();
            $table->foreign('condition_category_id')->references('id')->on('attention_categories')->nullOnDelete();
            $table->foreign('condition_sede_id')->references('id')->on('attention_sedes')->nullOnDelete();
            $table->foreign('assign_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assign_to_department_id')->references('id')->on('attention_departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attention_routing_rules');
    }
};
