<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ai_batch_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 100)->index();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('successful')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('rate_limited')->default(0);
            $table->unsignedInteger('budget_exceeded')->default(0);
            $table->decimal('total_cost', 12, 4)->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->decimal('avg_latency_ms', 10, 2)->default(0);
            $table->decimal('duration_seconds', 12, 3)->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ai_batch_metrics');
    }
};
