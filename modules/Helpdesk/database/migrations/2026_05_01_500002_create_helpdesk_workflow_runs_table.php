<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('helpdesk_workflows')->cascadeOnDelete();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('status')->default('running'); // running|completed|failed|cancelled
            $table->string('current_node_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('workflow_id');
            $table->index('status');
            $table->index('conversation_id');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_workflow_runs');
    }
};
