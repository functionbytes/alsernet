<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ai_prompt_cases', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);
            $table->string('name');
            $table->text('description'); // qué preguntas cubre; lo usa el clasificador
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->longText('instructions');
            $table->json('allowed_tools')->nullable(); // null = todas
            $table->json('knowledge_keys')->nullable();
            $table->string('escalation', 16)->default('on_doubt'); // never|on_doubt|always
            $table->text('escalation_message')->nullable();
            $table->json('examples')->nullable(); // [{question, answer}]
            $table->json('keywords')->nullable(); // [string]
            $table->json('filters')->nullable(); // {channels, locales, url_contains, logged_in, hours}
            $table->json('test_questions')->nullable(); // [{question, expect_tools, expect_escalate, must_contain, must_not_contain}]
            $table->string('channel', 32)->nullable(); // override por canal del caso global con la misma key
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['key', 'channel']);
            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_prompt_cases');
    }
};
