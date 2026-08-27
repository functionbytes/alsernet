<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_label_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('image_vertical')->nullable();
            $table->string('image_horizontal')->nullable();
            $table->string('label_text')->default('Precio recomendado:');
            $table->json('fields')->nullable();
            $table->json('positions_vertical')->nullable();
            $table->json('positions_horizontal')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_label_templates');
    }
};
