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
        Schema::create('chat_channel_whatsapps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('channel_whatsapps_account_id_index');
            $table->string('phone_number')->index('channel_whatsapps_phone_number_index');
            $table->string('provider')->default('whatsapp_cloud')->index('channel_whatsapps_provider_index');
            $table->json('provider_config');
            $table->string('webhook_url')->nullable();
            $table->json('message_templates')->nullable();
            $table->json('additional_attributes')->nullable();
            $table->timestamps();

            $table->unique(['phone_number'], 'channel_whatsapps_phone_number_unique');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_channel_whatsapps');
    }
};
