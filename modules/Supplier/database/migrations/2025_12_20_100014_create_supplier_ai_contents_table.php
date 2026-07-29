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
        Schema::create('supplier_ai_contents', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique()->comment('ULID unique identifier');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('cascade')->comment('FK to suppliers');
            $table->foreignId('supplier_product_id')->nullable()->comment('FK to supplier_products (nullable)');
            $table->unsignedInteger('product_id')->nullable()->comment('FK to aalv_product PrestaShop (nullable)');
            $table->string('erp_reference', 100)->nullable()->comment('ERP/Management reference ID');
            $table->string('model_id', 100)->nullable()->comment('Shared model ID ERP/Web');
            $table->string('ean', 20)->nullable()->comment('EAN/Barcode');
            $table->enum('status', [
                'pending_generation',
                'generating',
                'pending_validation',
                'in_review',
                'needs_revision',
                'validated',
                'published',
                'rejected',
                'error_insufficient_info',
                'error_source_unavailable',
                'error_generation_failed',
            ])->default('pending_generation')->comment('Content status');
            $table->string('generated_name', 255)->nullable()->comment('Generated product name');
            $table->text('short_description')->nullable()->comment('Short description');
            $table->text('long_description')->nullable()->comment('Long description');
            $table->json('bullet_points')->nullable()->comment('Feature list');
            $table->string('seo_title', 70)->nullable()->comment('Meta title');
            $table->string('seo_description', 160)->nullable()->comment('Meta description');
            $table->string('seo_keywords', 255)->nullable()->comment('Keywords');
            $table->json('source_attributes')->nullable()->comment('Original ERP attributes');
            $table->json('sources_used')->nullable()->comment('Consulted sources (IDs + URLs)');
            $table->foreignId('prompt_id')->nullable()->constrained('supplier_prompts')->onDelete('set null')->comment('FK to supplier_prompts used');
            $table->json('generation_metadata')->nullable()->comment('Generation metadata (AI model, tokens, etc.)');
            $table->text('error_message')->nullable()->comment('Error message if failed');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null')->comment('FK to users');
            $table->timestamp('validated_at')->nullable();
            $table->text('rejection_reason')->nullable()->comment('Rejection reason');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('synced_to_erp_at')->nullable()->comment('When synced to ERP');
            $table->timestamps();

            $table->index(['supplier_id', 'status'], 'contents_supplier_status_idx');
            $table->index('erp_reference');
            $table->index('model_id');
            $table->index('ean');
            $table->index('status');
            $table->index('validated_by');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_ai_contents');
    }
};
