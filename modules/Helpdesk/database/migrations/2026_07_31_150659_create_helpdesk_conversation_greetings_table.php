<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_conversation_greetings', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->nullable()->comment('null = todos los canales');
            $table->string('language')->nullable()->comment('null = generico (se traduce al vuelo); ISO 639-1 = mensaje redactado para ese idioma');
            $table->text('message');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('channel');
            $table->index('is_active');
            $table->unique(['channel', 'language'], 'helpdesk_conversation_greetings_channel_language_unique');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_conversation_greetings');
    }
};
