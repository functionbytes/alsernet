<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ai_agent_flows')) {
            Schema::connection('helpdesk')->create('helpdesk_ai_agent_flows', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';

                $table->id();
                $table->foreignId('ai_agent_id')
                    ->constrained('helpdesk_ai_agents')
                    ->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('trigger_type', ['message', 'intent', 'keyword', 'conversation_start']);
                $table->json('trigger_conditions')->nullable();
                $table->json('nodes')->nullable();
                $table->json('edges')->nullable();
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->unsignedInteger('version')->default(1);
                $table->timestamp('published_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('ai_agent_id');
                $table->index('status');
                $table->index(['ai_agent_id', 'status']);
            });
        }

        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ai_agent_flow_nodes')) {
            Schema::connection('helpdesk')->create('helpdesk_ai_agent_flow_nodes', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';

                $table->id();
                $table->foreignId('flow_id')
                    ->constrained('helpdesk_ai_agent_flows')
                    ->cascadeOnDelete();
                $table->string('node_id');
                $table->enum('type', ['input', 'prompt', 'condition', 'action', 'output']);
                $table->string('label')->nullable();
                $table->json('position')->nullable();
                $table->json('data')->nullable();

                $table->index('flow_id');
                $table->unique(['flow_id', 'node_id']);
            });
        }

        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ai_agent_sessions')) {
            Schema::connection('helpdesk')->create('helpdesk_ai_agent_sessions', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';

                $table->id();
                $table->foreignId('ai_agent_id')
                    ->constrained('helpdesk_ai_agents')
                    ->cascadeOnDelete();
                $table->foreignId('conversation_id')
                    ->nullable()
                    ->constrained('helpdesk_conversations')
                    ->cascadeOnDelete();
                $table->foreignId('customer_id')
                    ->nullable()
                    ->constrained('helpdesk_customers')
                    ->cascadeOnDelete();
                $table->foreignId('flow_id')
                    ->nullable()
                    ->constrained('helpdesk_ai_agent_flows')
                    ->cascadeOnDelete();
                $table->string('current_node_id')->nullable();
                $table->enum('status', ['active', 'completed', 'failed', 'paused'])->default('active');
                $table->json('context')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index('ai_agent_id');
                $table->index('conversation_id');
            });
        }

        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ai_agent_session_messages')) {
            Schema::connection('helpdesk')->create('helpdesk_ai_agent_session_messages', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';

                $table->id();
                $table->foreignId('session_id')
                    ->constrained('helpdesk_ai_agent_sessions')
                    ->cascadeOnDelete();
                $table->enum('role', ['user', 'assistant', 'system']);
                $table->text('content');
                $table->string('node_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('session_id');
                $table->index('role');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_ai_agent_session_messages');
        Schema::connection('helpdesk')->dropIfExists('helpdesk_ai_agent_sessions');
        Schema::connection('helpdesk')->dropIfExists('helpdesk_ai_agent_flow_nodes');
        Schema::connection('helpdesk')->dropIfExists('helpdesk_ai_agent_flows');
    }
};
