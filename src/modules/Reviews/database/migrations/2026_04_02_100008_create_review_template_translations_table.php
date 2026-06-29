<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_template_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('template_id')
                ->constrained('review_reply_templates')
                ->cascadeOnDelete()
                ->comment('Template this translation belongs to');

            $table->string('language', 10)
                ->comment('ISO 639-1 language code');

            $table->text('template_text')
                ->comment('Translated version of the template body');

            $table->timestamps();

            $table->unique(['template_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_template_translations');
    }
};
