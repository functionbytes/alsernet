<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ai_action_runs', function (Blueprint $table) {
            $table->id();
            $table->string('action_key', 48);
            $table->string('source', 16); // ai|flow|agent|api|test
            $table->string('trace_id', 64)->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('status', 16); // ok|error|denied
            $table->string('error', 255)->nullable();
            $table->unsignedInteger('latency_ms')->default(0);
            $table->json('args_summary')->nullable(); // redactado: sin emails/teléfonos en claro
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action_key', 'created_at']);
            $table->index('trace_id');
            $table->index('conversation_id');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_action_runs');
    }
};
