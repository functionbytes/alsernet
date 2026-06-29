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

        if (! $schema->hasTable('helpdesk_chat_flow_sessions')) {
            $schema->create('helpdesk_chat_flow_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chat_flow_id')->index();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->string('current_node_id', 100)->nullable();
                $table->enum('status', ['active', 'completed', 'failed', 'transferred', 'abandoned'])->default('active');
                $table->json('context')->nullable();
                $table->string('trigger_type', 50);
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'status']);
                $table->index(['chat_flow_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_chat_flow_sessions');
    }
};
