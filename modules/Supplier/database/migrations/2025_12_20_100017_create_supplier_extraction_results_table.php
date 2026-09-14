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
        Schema::create('supplier_extraction_results', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->unsignedBigInteger('supplier_id')->nullable()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('supplier_sources')->cascadeOnDelete();
            $table->foreignId('mapping_id')->constrained('supplier_extraction_mappings')->cascadeOnDelete();
            $table->foreignId('execution_id')->nullable()->constrained('supplier_automation_executions')->nullOnDelete();
            $table->char('batch_id', 26)->nullable();
            $table->date('batch_date')->nullable();
            $table->string('reference', 100)->nullable()->index();
            $table->string('ean', 20)->nullable()->index();
            $table->string('source_url', 500)->nullable();
            $table->string('source_file')->nullable();
            $table->json('extracted_data');
            $table->json('raw_data')->nullable();
            $table->enum('extraction_quality', ['complete', 'partial', 'minimal', 'failed'])->default('complete');
            $table->json('missing_fields')->nullable();
            $table->enum('status', ['new', 'existing', 'updated', 'error'])->default('new');
            $table->char('hash', 64)->nullable();
            $table->char('previous_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'reference'], 'results_supplier_ref_idx');
            $table->index(['supplier_id', 'ean'], 'results_supplier_ean_idx');
            $table->index('batch_id');
            $table->index('batch_date');
            $table->index(['status', 'created_at'], 'results_status_created_idx');
            $table->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_extraction_results');
    }
};
