<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_mail_lists', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->foreignId('customer_id');
            $table->foreignId('contact_id');
            $table->char('name', 191);
            $table->char('from_email', 191)->nullable();
            $table->char('from_name', 191)->nullable();
            $table->text('remind_message')->nullable();
            $table->text('email_subscribe')->nullable();
            $table->text('email_unsubscribe')->nullable();
            $table->text('email_daily')->nullable();
            $table->integer('send_welcome_email')->default(0);
            $table->integer('unsubscribe_notification')->default(0);
            $table->char('status', 191)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('subscribe_confirmation')->default(1);
            $table->integer('all_sending_servers')->nullable()->default(0);
            $table->text('embedded_form_options')->nullable();

            // Foreign Keys
            $table->foreign('contact_id')
                ->references('id')
                ->on('mailing_contacts')
                ->onDelete('cascade');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_mail_lists');
    }
};
