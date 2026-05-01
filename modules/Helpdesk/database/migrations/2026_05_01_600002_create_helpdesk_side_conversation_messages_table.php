<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_side_conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('side_conversation_id');
            $table->enum('author_type', ['agent', 'external']);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('from_email')->nullable();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('side_conversation_id')
                ->references('id')
                ->on('helpdesk_side_conversations')
                ->cascadeOnDelete();

            $table->index(['side_conversation_id', 'created_at']);
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_side_conversation_messages');
    }
};
