<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_channel_apis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable()->index('hd_channel_apis_account_id_index');
            $table->string('identifier')->index('hd_channel_apis_identifier_index');
            $table->string('webhook_url')->nullable();
            $table->string('hmac_token')->nullable()->unique('hd_channel_apis_hmac_token_unique');
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('active')->default(true);
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->unique(['identifier'], 'hd_channel_apis_identifier_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_channel_apis');
    }
};
