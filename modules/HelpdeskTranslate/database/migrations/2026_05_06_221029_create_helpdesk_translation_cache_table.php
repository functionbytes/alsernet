<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_translation_cache', function (Blueprint $table) {
            $table->id();
            $table->char('text_hash', 64)->unique()->comment('sha256(text|source|target|provider)');
            $table->longText('text_source');
            $table->longText('text_translated');
            $table->string('source_lang', 8);
            $table->string('target_lang', 8);
            $table->string('provider', 32)->default('deepl');
            $table->unsignedInteger('hits')->default(1);
            $table->unsignedInteger('chars_saved')->default(0)->comment('Source-text length × hits, used for billing estimates');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['source_lang', 'target_lang']);
            $table->index('last_used_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_translation_cache');
    }
};
