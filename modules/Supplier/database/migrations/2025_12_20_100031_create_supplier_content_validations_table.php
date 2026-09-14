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
        Schema::create('supplier_content_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('supplier_ai_contents')->onDelete('cascade')->comment('FK to supplier_ai_contents table');

            // Quality Metrics
            $table->decimal('quality_score', 5, 2)->nullable()->comment('Overall quality score 0-100');
            $table->decimal('readability_score', 5, 2)->nullable()->comment('Flesch-Kincaid readability score');
            $table->decimal('keyword_density', 5, 4)->nullable()->comment('Percentage of keywords in content');
            $table->decimal('unique_words_ratio', 5, 4)->nullable()->comment('Lexical diversity ratio');
            $table->decimal('sentence_avg_length', 6, 2)->nullable()->comment('Average sentence length in words');

            // Specific Validations
            $table->boolean('has_required_sections')->default(false)->comment('Contains all required sections');
            $table->boolean('has_brand_terms')->default(false)->comment('Contains brand terminology');
            $table->boolean('has_prohibited_words')->default(false)->comment('Contains prohibited words');
            $table->decimal('plagiarism_score', 5, 2)->nullable()->comment('Plagiarism score 0-100 (0=original)');

            // Results
            $table->enum('validation_status', ['passed', 'failed', 'needs_review'])->default('needs_review')->comment('Validation result status');
            $table->json('issues')->nullable()->comment('List of issues found');
            $table->json('suggestions')->nullable()->comment('Improvement suggestions');

            // Audit
            $table->string('validated_by', 50)->nullable()->comment('system or user_id who validated');
            $table->timestamp('validated_at')->nullable()->comment('When validation was performed');

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('content_id');
            $table->index('validation_status');
            $table->index(['quality_score', 'validation_status'], 'validations_quality_status_idx');
            $table->index('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_content_validations');
    }
};
