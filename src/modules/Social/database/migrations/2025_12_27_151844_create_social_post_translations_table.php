<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_post_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->string('language', 10)->index();
            $table->text('content');
            $table->string('translation_provider', 50)->nullable(); // 'google', 'deepl', 'manual'
            $table->decimal('confidence_score', 3, 2)->nullable(); // 0.00 to 1.00
            $table->foreignId('translated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('translated_at');
            $table->timestamps();

            $table->unique(['post_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_translations');
    }
};
