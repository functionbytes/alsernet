<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('trigger_type', ['conversation_closed', 'manual', 'csat_low', 'tag_added'])
                ->default('conversation_closed')
                ->index();
            $table->json('trigger_config')->nullable();
            $table->json('questions');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('helpdesk_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('helpdesk_surveys')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->json('answers')->nullable();
            $table->string('survey_token', 64)->unique();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('answered_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['survey_id', 'answered_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_survey_responses');
        Schema::connection($this->connection)->dropIfExists('helpdesk_surveys');
    }
};
