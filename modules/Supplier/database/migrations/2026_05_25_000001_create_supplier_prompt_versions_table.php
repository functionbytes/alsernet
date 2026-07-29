<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained('supplier_prompts')->onDelete('cascade');
            $table->unsignedInteger('version')->comment('Version number before this update');
            $table->string('label', 255);
            $table->string('scope', 50);
            $table->string('content_type', 50)->nullable();
            $table->string('output_language', 10)->nullable();
            $table->string('tone', 50)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('seo_focus')->default(false);
            $table->boolean('enable_web_search')->default(false);
            $table->longText('prompt_template');
            $table->foreignId('saved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['prompt_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_prompt_versions');
    }
};
