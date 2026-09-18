<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_channel_instagrams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable()->index('hd_channel_instagrams_account_id_index');
            $table->string('instagram_id')->index('hd_channel_instagrams_instagram_id_index');
            $table->string('username')->nullable();
            $table->text('user_access_token');
            $table->text('page_access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->unsignedBigInteger('facebook_page_id')->nullable()->index('hd_channel_instagrams_facebook_page_id_index');
            $table->json('additional_attributes')->nullable();
            $table->timestamps();

            $table->unique(['instagram_id'], 'hd_channel_instagrams_instagram_id_unique');

            // FK to helpdesk_channel_facebooks (no FK to chat_channel_facebooks)
            $table->foreign('facebook_page_id')
                ->references('id')
                ->on('helpdesk_channel_facebooks')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_channel_instagrams');
    }
};
