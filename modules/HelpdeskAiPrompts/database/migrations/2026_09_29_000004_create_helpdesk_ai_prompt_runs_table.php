<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ai_prompt_runs', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 64)->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('case_key', 64)->nullable();
            $table->string('routed_by', 16); // keyword|llm|default|forced|none
            $table->string('action', 16); // respond|escalate
            $table->json('used_tools')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->tinyInteger('feedback')->nullable(); // 1 / -1
            $table->timestamp('created_at')->useCurrent();

            $table->index('trace_id');
            $table->index('conversation_id');
            $table->index('item_id');
            $table->index('case_key');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ai_prompt_runs');
    }
};
