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
        Schema::create('chat_channel_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('channel_sms_account_id_index');
            $table->string('provider')->default('bandwidth');
            $table->string('phone_number')->index('channel_sms_phone_number_index');
            $table->string('api_key');
            $table->string('api_secret');
            $table->string('application_id')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('active')->default(true);
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->unique(['phone_number'], 'channel_sms_phone_number_unique');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_channel_sms');
    }
};
