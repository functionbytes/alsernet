<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attention_satisfaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attention_id')
                ->constrained('attentions')
                ->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned()->comment('Rating 1-5 estrellas');
            $table->text('comment')->nullable()->comment('Comentario adicional');
            $table->timestamp('submitted_at')->nullable()->comment('Fecha de envío de la encuesta');
            $table->timestamps();

            $table->index('attention_id');
            $table->index('rating');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attention_satisfaction');
    }
};
