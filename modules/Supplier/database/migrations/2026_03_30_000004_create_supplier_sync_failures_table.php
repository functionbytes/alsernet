<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_sync_failures', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('sync_type', 50)->index();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('erp_id')->nullable();
            $table->json('changed_data');
            $table->json('context')->nullable();
            $table->text('error_message');
            $table->string('error_code', 100)->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->unsignedSmallInteger('max_retries')->default(3);
            $table->string('failure_status', 20)->default('pending')->index();
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('supplier_sync_batches')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->index(['sync_type', 'failure_status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_sync_failures');
    }
};
