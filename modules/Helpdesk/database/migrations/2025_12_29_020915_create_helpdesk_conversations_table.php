<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('subject');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->string('priority')->default('normal');
            $table->boolean('is_archived')->default(false);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('helpdesk_customers')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('helpdesk_conversation_statuses')->onDelete('set null');

            $table->index('customer_id');
            $table->index('status_id');
            $table->index('assignee_id');
            $table->index('is_archived');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_conversations');
    }
};
