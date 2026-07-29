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
        Schema::create('supplier_extraction_mappings', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->foreignId('source_id')->constrained('supplier_sources')->cascadeOnDelete();
            $table->string('name');
            $table->enum('source_type', ['website', 'ftp_excel', 'ftp_csv', 'ftp_pdf', 'upload_pdf', 'upload_excel', 'api']);
            $table->json('field_mappings');
            $table->json('search_config')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('transform_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['source_id', 'is_active'], 'mappings_source_active_idx');
            $table->index('source_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_extraction_mappings');
    }
};
