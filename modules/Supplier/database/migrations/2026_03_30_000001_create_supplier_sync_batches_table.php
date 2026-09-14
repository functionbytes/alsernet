<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->string('batch_name');
            $table->string('sync_type', 50);
            $table->string('status', 20)->default('pending')->index();
            $table->string('priority', 20)->default('normal');
            $table->unsignedInteger('batch_size')->default(100);
            $table->unsignedInteger('total_batches')->default(0);
            $table->unsignedInteger('processed_batches')->default(0);
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->unsignedTinyInteger('retry_attempt')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(3);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_retry_at')->nullable();
            $table->float('duration_seconds')->nullable();
            $table->string('triggered_by', 50)->nullable();
            $table->string('trigger_details')->nullable();
            $table->json('filter_criteria')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->index(['sync_type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_sync_batches');
    }
};
