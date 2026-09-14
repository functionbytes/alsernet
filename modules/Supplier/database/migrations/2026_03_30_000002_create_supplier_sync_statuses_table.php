<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_sync_statuses', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('sync_type', 50)->index();
            $table->string('sync_scope', 50)->default('all');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('synced_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->unsignedInteger('skipped_items')->default(0);
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->float('elapsed_seconds')->nullable();
            $table->float('memory_used_mb')->nullable();
            $table->string('triggered_by', 50)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('supplier_sync_batches')->nullOnDelete();
            $table->index(['sync_type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_sync_statuses');
    }
};
