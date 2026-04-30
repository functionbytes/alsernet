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
        Schema::create('chat_channel_emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('channel_emails_account_id_index');
            $table->string('email')->unique('channel_emails_email_unique');
            $table->string('forward_to_email')->unique('channel_emails_forward_to_email_unique');
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

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_channel_emails');
    }
};
