<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_maillists', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('default_subject')->nullable();
            $table->string('contact_company')->nullable();
            $table->string('contact_state')->nullable();
            $table->string('contact_city')->nullable();
            $table->string('contact_zip')->nullable();
            $table->string('contact_country_id')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_url')->nullable();
            $table->string('contact_address_1')->nullable();
            $table->string('contact_address_2')->nullable();
            $table->string('subscribe_confirmation')->default('1');
            $table->string('send_welcome_email')->default('0');
            $table->string('unsubscribe_notification')->default('0');
            $table->text('remind_message')->nullable();
            $table->text('subscribe_form_embed_code')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_maillists');
    }
};
