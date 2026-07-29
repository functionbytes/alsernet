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
        Schema::create('supplier_automation_retry_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('execution_id')->index();
            $table->integer('attempt_number')->default(1);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('retry_at')->index();
            $table->enum('retry_strategy', ['immediate', 'linear', 'exponential'])->default('exponential');
            $table->text('last_error')->nullable();
            $table->enum('status', ['pending', 'retrying', 'exhausted', 'succeeded'])->default('pending')->index();
            $table->timestamps();

            $table->foreign('execution_id')
                ->references('id')
                ->on('supplier_automation_executions')
                ->onDelete('cascade');

            $table->index(['status', 'retry_at'], 'retry_status_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_retry_queue');
    }
};
