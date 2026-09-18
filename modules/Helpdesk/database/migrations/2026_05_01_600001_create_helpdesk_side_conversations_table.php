<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_side_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_conversation_id');
            $table->string('subject');
            $table->enum('participant_type', ['team', 'external_email']);
            $table->string('participant_email')->nullable();
            $table->unsignedBigInteger('participant_user_id')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->unsignedBigInteger('created_by');
            $table->string('reply_token', 64)->unique()->nullable();
            $table->timestamps();

            $table->foreign('parent_conversation_id')
                ->references('id')
                ->on('helpdesk_conversations')
                ->cascadeOnDelete();

            $table->index('parent_conversation_id');
            $table->index('status');
            $table->index('reply_token');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_side_conversations');
    }
};
