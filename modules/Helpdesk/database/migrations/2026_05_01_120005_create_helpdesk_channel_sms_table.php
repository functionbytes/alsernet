<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_channel_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable()->index('hd_channel_sms_account_id_index');
            $table->string('provider')->default('bandwidth');
            $table->string('phone_number')->index('hd_channel_sms_phone_number_index');
            $table->string('api_key');
            $table->string('api_secret');
            $table->string('application_id')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('active')->default(true);
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->unique(['phone_number'], 'hd_channel_sms_phone_number_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_channel_sms');
    }
};
