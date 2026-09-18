<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('helpdesk')->hasTable('helpdesk_user_conversation_meta')) {
            return;
        }

        Schema::connection('helpdesk')->create('helpdesk_user_conversation_meta', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('conversation_id');
            $table->timestamp('pinned_at')->nullable();
            $table->timestamp('muted_until')->nullable();
            $table->boolean('blocked')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'conversation_id'], 'hucm_user_conversation_unique');
            $table->index('conversation_id', 'hucm_conversation_id_index');

            $table->foreign('conversation_id')
                ->references('id')
                ->on('helpdesk_conversations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_user_conversation_meta');
    }
};
