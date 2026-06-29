<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_helpcenter_article_embeddings')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_helpcenter_article_embeddings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->string('locale', 8)->default('es');
            $table->integer('chunk_index');
            $table->text('chunk_text');
            $table->longText('embedding'); // JSON array of floats
            $table->string('model', 64);
            $table->integer('dimensions');
            $table->timestamps();

            $table->index(['article_id', 'locale']);
            $table->fullText(['chunk_text']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_helpcenter_article_embeddings');
    }
};
