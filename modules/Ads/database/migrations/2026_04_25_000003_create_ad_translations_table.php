<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->timestamps();

            $table->unique(['ad_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_translations');
    }
};
