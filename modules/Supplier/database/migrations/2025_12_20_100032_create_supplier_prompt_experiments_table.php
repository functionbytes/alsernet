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
        Schema::create('supplier_prompt_experiments', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique()->comment('ULID unique identifier');
            $table->string('name', 255)->comment('Experiment name');
            $table->text('description')->nullable()->comment('Experiment description');
            $table->string('prompt_type', 50)->comment('Type of prompt being tested');

            // Variants
            $table->foreignId('control_prompt_id')->nullable()->constrained('supplier_prompts')->onDelete('set null')->comment('Control variant prompt');
            $table->foreignId('variant_prompt_id')->nullable()->constrained('supplier_prompts')->onDelete('set null')->comment('Test variant prompt');

            // Configuration
            $table->decimal('traffic_split', 3, 2)->default(0.50)->comment('Traffic allocation 0.00-1.00');
            $table->integer('sample_size')->default(100)->comment('Minimum sample size needed');

            // Status
            $table->enum('status', ['draft', 'running', 'completed', 'cancelled'])->default('draft')->comment('Experiment status');
            $table->timestamp('started_at')->nullable()->comment('When experiment started');
            $table->timestamp('ended_at')->nullable()->comment('When experiment ended');

            // Results
            $table->json('results')->nullable()->comment('Experiment results and statistics');
            $table->foreignId('winner_prompt_id')->nullable()->constrained('supplier_prompts')->onDelete('set null')->comment('Winning prompt variant');
            $table->decimal('statistical_significance', 5, 4)->nullable()->comment('Statistical significance level');

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('prompt_type');
            $table->index('status');
            $table->index(['status', 'started_at'], 'experiments_status_started_idx');
            $table->index('control_prompt_id');
            $table->index('variant_prompt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_prompt_experiments');
    }
};
