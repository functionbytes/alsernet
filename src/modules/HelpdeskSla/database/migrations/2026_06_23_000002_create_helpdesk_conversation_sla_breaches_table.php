<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_conversation_sla_breaches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('sla_type'); // first_response | resolution
            $table->timestamp('due_at')->nullable();
            $table->timestamp('breached_at');
            $table->integer('minutes_over')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('helpdesk_conversations')->cascadeOnDelete();

            $table->index('conversation_id');
            $table->index('breached_at');
            $table->index(['sla_type', 'resolved']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_conversation_sla_breaches');
    }
};
