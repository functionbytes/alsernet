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
        Schema::create('chat_csat_survey_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('customer_id')->index('csat_survey_responses_customer_id_foreign');
            $table->unsignedBigInteger('assigned_agent_id')->nullable()->index('csat_survey_responses_assigned_agent_id_foreign');
            $table->integer('rating')->nullable();
            $table->text('feedback')->nullable();
            $table->string('survey_token')->unique('csat_survey_responses_survey_token_unique');
            $table->timestamp('submitted_at')->nullable()->index('csat_survey_responses_submitted_at_index');
            $table->timestamps();

            $table->index(['account_id', 'rating'], 'csat_survey_responses_account_id_rating_index');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('assigned_agent_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('chat_customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_csat_survey_responses');
    }
};
