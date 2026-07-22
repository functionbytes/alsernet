<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger ligero de uso LLM (observabilidad de coste). Una fila por llamada
 * saliente a un proveedor — insert directo, sin updates (no updated_at).
 * Lo alimentan AgentLlmService (summary/classification) y AiAgentFlowEngine
 * (chatflow); lo leen el comando helpdesk:ai-usage y el límite diario de gasto.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ai_usage')) {
            return;
        }

        $schema->create('helpdesk_ai_usage', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->id();
            $table->string('provider', 32);
            $table->string('model', 128)->nullable();
            // summary | classification | chatflow | other
            $table->string('feature', 32)->default('other');
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->boolean('success')->default(true);
            // HTTP status del proveedor cuando la llamada falla (null en éxito/excepción)
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['feature', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_usage');
    }
};
