<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_channel_whatsapps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable()->index('hd_channel_whatsapps_account_id_index');
            $table->string('phone_number')->index('hd_channel_whatsapps_phone_number_index');
            $table->string('provider')->default('whatsapp_cloud')->index('hd_channel_whatsapps_provider_index');
            $table->json('provider_config');
            $table->string('webhook_url')->nullable();
            $table->json('message_templates')->nullable();
            $table->json('additional_attributes')->nullable();
            $table->timestamps();

            $table->unique(['phone_number'], 'hd_channel_whatsapps_phone_number_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_channel_whatsapps');
    }
};
