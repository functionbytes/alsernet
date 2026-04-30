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
        Schema::create('chat_sla_applied_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sla_id')->index('sla_applied_conversations_sla_id_index');
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->boolean('first_response_breached')->default(false);
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('resolution_breached')->default(false);
            $table->timestamps();

            $table->index(['first_response_breached', 'resolution_breached'], 'sla_breach_index');

            // Foreign keys
            $table->foreign('sla_id')->references('id')->on('chat_sla_policies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sla_applied_conversations');
    }
};
