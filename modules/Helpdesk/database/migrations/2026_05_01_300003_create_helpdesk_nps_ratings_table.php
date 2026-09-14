<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_nps_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('helpdesk_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->tinyInteger('score')->nullable()->comment('0-10 NPS score');
            $table->text('comment')->nullable();
            $table->string('survey_token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index(['conversation_id', 'answered_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_nps_ratings');
    }
};
