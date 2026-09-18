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

        if (! $schema->hasTable('helpdesk_chat_flow_executions')) {
            $schema->create('helpdesk_chat_flow_executions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('session_id')->index();
                $table->string('node_id', 100);
                $table->string('node_type', 50);
                $table->json('input')->nullable();
                $table->json('output')->nullable();
                $table->enum('status', ['success', 'failed', 'skipped', 'pending'])->default('pending');
                $table->integer('duration_ms')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('executed_at');
                $table->timestamps();

                $table->index(['session_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_chat_flow_executions');
    }
};
