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
        Schema::create('supplier_prompts', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique()->comment('ULID unique identifier');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedInteger('category_id')->nullable()->comment('FK to category (nullable)');
            $table->foreignId('source_id')->nullable()->constrained('supplier_sources')->onDelete('cascade')->comment('FK to supplier_sources (nullable)');
            $table->enum('scope', ['global', 'supplier', 'category', 'supplier_category', 'source'])->comment('Prompt scope');
            $table->string('label', 255)->comment('Prompt name');
            $table->enum('content_type', [
                'description',
                'short_description',
                'title',
                'seo_title',
                'seo_description',
                'seo_keywords',
                'metadata',
                'features',
                'specifications',
                'benefits',
            ])->default('description')->comment('Content type for this prompt');
            $table->text('prompt_template')->comment('Prompt content template');
            $table->string('output_language', 10)->default('es-ES')->comment('Output language: es-ES, en-GB');
            $table->string('tone', 50)->default('commercial')->comment('Tone: technical, commercial, formal');
            $table->integer('priority')->default(1)->comment('Priority (1 = highest). Determines which to use');
            $table->boolean('seo_focus')->default(false)->comment('Optimize for SEO?');
            $table->json('required_sections')->nullable()->comment('Required sections');
            $table->integer('version')->default(1)->comment('Prompt version');
            $table->boolean('is_default')->default(false)->comment('Is default prompt?');
            $table->boolean('is_active')->default(true)->comment('Active/inactive');
            $table->boolean('is_template')->default(false)->comment('Whether this is a template');
            $table->string('template_category', 50)->nullable()->comment('Template category if applicable');
            $table->foreignId('cloned_from_template_id')->nullable()->constrained('supplier_prompts')->nullOnDelete()->comment('Template this was cloned from');
            $table->timestamps();

            $table->index(['supplier_id', 'category_id', 'source_id'], 'prompts_supplier_cat_src_idx');
            $table->index(['scope', 'is_active'], 'prompts_scope_active_idx');
            $table->index(['scope', 'priority'], 'prompts_scope_priority_idx');
            $table->index('is_default');
            $table->index('is_template');
            $table->index('template_category');
            $table->index(['is_template', 'template_category'], 'template_category_idx');
            $table->index(['supplier_id', 'category_id', 'content_type'], 'supplier_category_content_idx');
            $table->index('content_type', 'content_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_prompts');
    }
};
