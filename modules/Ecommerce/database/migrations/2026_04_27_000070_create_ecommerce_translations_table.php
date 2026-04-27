<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 5);
            $table->string('field', 50);
            $table->text('value');
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'ecommerce_translations_unique');
            $table->index(['translatable_type', 'translatable_id'], 'ecommerce_translations_morph_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_translations');
    }
};
