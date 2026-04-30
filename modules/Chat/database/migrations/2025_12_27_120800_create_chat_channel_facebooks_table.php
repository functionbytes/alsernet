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
        Schema::create('chat_channel_facebooks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('channel_facebook_pages_account_id_index');
            $table->string('page_id')->index('channel_facebook_pages_page_id_index');
            $table->string('page_name');
            $table->text('page_access_token');
            $table->text('user_access_token')->nullable();
            $table->string('instagram_id')->nullable();
            $table->json('additional_attributes')->nullable();
            $table->timestamps();

            $table->unique(['page_id'], 'channel_facebook_pages_page_id_unique');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_channel_facebooks');
    }
};
