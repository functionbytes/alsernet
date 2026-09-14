<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_channel_webs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable()->index('hd_channel_webs_account_id_index');
            $table->string('website_url')->nullable();
            $table->string('website_token')->unique('hd_channel_webs_website_token_unique');
            $table->string('hmac_token')->nullable()->unique('hd_channel_webs_hmac_token_unique');
            $table->string('widget_color')->default('#90bb13');
            $table->string('widget_position')->default('right');
            $table->string('widget_bubble_launcher_title')->nullable();
            $table->string('widget_launcher_icon')->nullable();
            $table->string('welcome_title')->default('Hola!');
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
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_channel_webs');
    }
};
