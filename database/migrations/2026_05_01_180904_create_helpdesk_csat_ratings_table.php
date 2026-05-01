<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_csat_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();      // 1-5
            $table->text('comment')->nullable();
            $table->string('survey_token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'answered_at']);
            $table->index('rating');
            $table->foreign('conversation_id')->references('id')->on('helpdesk_conversations')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('helpdesk_customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_csat_ratings');
    }
};
