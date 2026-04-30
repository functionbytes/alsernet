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
        Schema::create('chat_channel_instagrams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('channel_instagrams_account_id_index');
            $table->string('instagram_id')->index('channel_instagrams_instagram_id_index');
            $table->string('username')->nullable();
            $table->text('user_access_token');
            $table->text('page_access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->unsignedBigInteger('facebook_page_id')->nullable()->index('channel_instagrams_facebook_page_id_index');
            $table->json('additional_attributes')->nullable();
            $table->timestamps();

            $table->unique(['instagram_id'], 'channel_instagrams_instagram_id_unique');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('facebook_page_id')->references('id')->on('chat_channel_facebooks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_channel_instagrams');
    }
};
