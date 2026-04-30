<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_results_shown', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('entity_id', 64);
            $table->enum('entity_type', ['product', 'post', 'cms']);
            $table->integer('position');
            $table->integer('was_clicked')->default(0);
            $table->timestamp('display_date');
            $table->timestamps();

            $table->index('conversation_id', 'hd_results_conv_idx');
            $table->index('entity_id', 'hd_results_entity_idx');
            $table->index('entity_type', 'hd_results_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_results_shown');
    }
};
