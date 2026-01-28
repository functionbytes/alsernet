<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_template_langs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('mailing_template_id')->constrained('mailing_templates')->onDelete('cascade');
            $table->foreignId('lang_id')->constrained('langs')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->string('preheader')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();

            $table->unique(['mailing_template_id', 'lang_id'], 'idx_89470');
            $table->index('lang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_template_langs');
    }
};
