<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_requirement_langs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_requirement_id')->constrained('document_type_requirements')->cascadeOnDelete();
            $table->foreignId('lang_id')->constrained('langs')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('help_text')->nullable();
            $table->timestamps();
            $table->unique(['document_type_requirement_id', 'lang_id']);
            $table->index('lang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_requirement_langs');
    }
};
