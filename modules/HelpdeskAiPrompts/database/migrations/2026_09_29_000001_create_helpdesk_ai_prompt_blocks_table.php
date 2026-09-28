<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ai_prompt_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);
            $table->string('kind', 16); // 'base' | 'knowledge'
            $table->string('name');
            $table->longText('content');
            $table->string('channel', 32)->nullable(); // null = global
            $table->string('locale', 5)->nullable(); // null = cualquiera
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['key', 'channel', 'locale']);
            $table->index(['kind', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_prompt_blocks');
    }
};
