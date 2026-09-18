<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_channel_emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable()->index('hd_channel_emails_account_id_index');
            $table->string('email')->nullable()->unique('hd_channel_emails_email_unique');
            $table->string('forward_to_email')->nullable()->unique('hd_channel_emails_forward_to_email_unique');
            $table->boolean('imap_enabled')->default(false);
            $table->string('imap_address')->nullable();
            $table->integer('imap_port')->nullable();
            $table->string('imap_login')->nullable();
            $table->text('imap_password')->nullable();
            $table->boolean('imap_enable_ssl')->default(true);
            $table->boolean('smtp_enabled')->default(false);
            $table->string('smtp_address')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_login')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_domain')->nullable();
            $table->string('smtp_authentication')->default('login');
            $table->boolean('smtp_enable_starttls_auto')->default(true);
            $table->boolean('smtp_enable_ssl_tls')->default(false);
            $table->string('smtp_openssl_verify_mode')->default('none');
            $table->string('provider')->nullable();
            $table->json('provider_config')->nullable();
            $table->boolean('verified_for_sending')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_channel_emails');
    }
};
