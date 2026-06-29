<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ai_tool_executions')) {
            return;
        }

        $schema->create('helpdesk_ai_tool_executions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('session_id');
            $table->json('arguments')->nullable();
            $table->json('result')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->foreign('tool_id')
                ->references('id')
                ->on('helpdesk_ai_agent_tools')
                ->cascadeOnDelete();

            $table->foreign('session_id')
                ->references('id')
                ->on('helpdesk_ai_agent_sessions')
                ->cascadeOnDelete();

            $table->index(['tool_id', 'created_at']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_tool_executions');
    }
};
