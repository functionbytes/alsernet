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
        Schema::create('supplier_extraction_batches', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->unsignedBigInteger('supplier_id')->nullable()->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('supplier_sources')->nullOnDelete();
            $table->date('batch_date');
            $table->enum('batch_type', ['daily', 'manual', 'incremental', 'full_sync'])->default('daily');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('total_items')->default(0);
            $table->integer('new_items')->default(0);
            $table->integer('updated_items')->default(0);
            $table->integer('unchanged_items')->default(0);
            $table->integer('failed_items')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'batch_date'], 'batches_supplier_date_idx');
            $table->index(['status', 'created_at'], 'batches_status_created_idx');
            $table->index('batch_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_extraction_batches');
    }
};
