<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->string('locale_code', 10);
            $table->text('translated_text');
            $table->string('detected_language', 10)->nullable();
            $table->timestamp('translated_at')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'locale_code']);
            $table->index('locale_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_translations');
    }
};
