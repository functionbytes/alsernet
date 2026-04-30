<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('helpdesk')->hasTable('helpdesk_conversation_drafts')) {
            return;
        }

        Schema::connection('helpdesk')->create('helpdesk_conversation_drafts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id'], 'hcd_conversation_user_unique');
            $table->index('user_id', 'hcd_user_id_index');

            $table->foreign('conversation_id')
                ->references('id')
                ->on('helpdesk_conversations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_conversation_drafts');
    }
};
