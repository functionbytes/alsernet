<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attention_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attention_id')
                ->constrained('attentions')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->longText('content');
            $table->timestamps();

            $table->index('attention_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attention_notes');
    }
};
