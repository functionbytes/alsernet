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
        Schema::create('chat_channel_webs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('channel_web_widgets_account_id_index');
            $table->string('website_url');
            $table->string('website_token')->unique('channel_web_widgets_website_token_unique');
            $table->string('hmac_token')->unique('channel_web_widgets_hmac_token_unique');
            $table->string('widget_color')->default('#1f93ff');
            $table->string('widget_position')->default('right');
            $table->string('widget_bubble_launcher_title')->nullable();
            $table->string('widget_launcher_icon')->nullable();
            $table->string('welcome_title')->default('Hello!');
            $table->text('welcome_tagline')->nullable();
            $table->boolean('pre_chat_form_enabled')->default(false);
            $table->json('pre_chat_form_options')->nullable();
            $table->boolean('offline_message_enabled')->default(true);
            $table->text('offline_message')->nullable();
            $table->boolean('show_availability_status')->default(true);
            $table->json('business_hours')->nullable();
            $table->string('widget_bubble_color')->nullable();
            $table->json('widget_custom_styles')->nullable();
            $table->string('reply_time_message')->nullable();
            $table->boolean('show_powered_by')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_channel_webs');
    }
};
