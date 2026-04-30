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
        Schema::create('chat_channel_apis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('channel_apis_account_id_index');
            $table->string('identifier')->index('channel_apis_identifier_index');
            $table->string('webhook_url')->nullable();
            $table->string('hmac_token')->unique('channel_apis_hmac_token_unique');
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('active')->default(true);
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->unique(['identifier'], 'channel_apis_identifier_unique');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_channel_apis');
    }
};
