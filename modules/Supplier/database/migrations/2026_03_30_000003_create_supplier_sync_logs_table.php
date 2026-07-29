<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->unsignedBigInteger('batch_id')->index();
            $table->unsignedBigInteger('status_id')->nullable()->index();
            $table->string('entity_type', 50)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->unsignedBigInteger('erp_id')->nullable();
            $table->string('action', 20);
            $table->string('result', 20)->index();
            $table->text('message')->nullable();
            $table->json('data_before')->nullable();
            $table->json('data_after')->nullable();
            $table->json('changes')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('triggered_by', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('supplier_sync_batches')->cascadeOnDelete();
            $table->foreign('status_id')->references('id')->on('supplier_sync_statuses')->nullOnDelete();
            $table->index(['entity_type', 'result']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_sync_logs');
    }
};
