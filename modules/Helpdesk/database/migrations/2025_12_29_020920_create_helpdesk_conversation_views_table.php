<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_conversation_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('helpdesk_conversations')->onDelete('cascade');

            $table->unique(['conversation_id', 'user_id'], 'idx_74129');
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_conversation_views');
    }
};
