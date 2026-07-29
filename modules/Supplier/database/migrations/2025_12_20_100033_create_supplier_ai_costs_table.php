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
        Schema::create('supplier_ai_costs', function (Blueprint $table) {
            $table->id();

            // References
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('content_id')->nullable()->comment('Related content ID if applicable');
            $table->string('batch_id', 100)->nullable()->comment('Batch identifier for grouping');

            // Usage Details
            $table->string('model', 50)->comment('AI model used: gpt-4o, gpt-4o-mini, claude-3, etc.');
            $table->string('operation_type', 50)->nullable()->comment('Type of operation: generation, validation, extraction');

            // Token Consumption
            $table->integer('input_tokens')->comment('Number of input tokens consumed');
            $table->integer('output_tokens')->comment('Number of output tokens generated');
            $table->integer('total_tokens')->storedAs('input_tokens + output_tokens')->comment('Total tokens (computed)');

            // Costs in USD
            $table->decimal('input_cost', 10, 6)->nullable()->comment('Cost for input tokens in USD');
            $table->decimal('output_cost', 10, 6)->nullable()->comment('Cost for output tokens in USD');
            $table->decimal('total_cost', 10, 6)->storedAs('COALESCE(input_cost, 0) + COALESCE(output_cost, 0)')->comment('Total cost in USD (computed)');

            // Metadata
            $table->string('request_id', 100)->nullable()->comment('API request ID for tracing');
            $table->integer('latency_ms')->nullable()->comment('Request latency in milliseconds');

            $table->timestamp('created_at')->useCurrent();

            // Indexes for reporting
            $table->index(['supplier_id', 'created_at'], 'costs_supplier_created_idx');
            $table->index(['model', 'created_at'], 'costs_model_created_idx');
            $table->index('batch_id');
            $table->index('operation_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_ai_costs');
    }
};
