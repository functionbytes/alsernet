<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_warranty_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->integer('default_duration_months')->nullable();
            $table->integer('max_duration_months')->nullable();
            $table->json('coverage_details')->nullable();
            $table->json('exclusions')->nullable();
            $table->decimal('cost_percentage', 5, 2)->nullable();
            $table->decimal('fixed_cost', 10, 2)->nullable();
            $table->boolean('transferable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_warranty_types');
    }
};
