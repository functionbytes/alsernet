<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->foreignId('customer_id');
            $table->text('type');
            $table->text('name');
            $table->text('subject')->nullable();
            $table->longText('plain')->nullable();
            $table->text('from_email')->nullable();
            $table->text('from_name')->nullable();
            $table->text('reply_to')->nullable();
            $table->text('status')->nullable();
            $table->integer('sign_dkim')->nullable();
            $table->integer('track_open')->nullable();
            $table->integer('track_click')->nullable();
            $table->integer('resend')->nullable();
            $table->timestamp('run_at')->nullable();
            $table->timestamp('delivery_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->text('template_source')->nullable();
            $table->text('last_error')->nullable();
            $table->text('image')->nullable();
            $table->foreignId('default_mail_list_id')->nullable();
            $table->foreignId('tracking_domain_id')->nullable();
            $table->integer('use_default_sending_server_from_email')->default(0);
            $table->text('preheader')->nullable();
            $table->integer('running_pid')->nullable();
            $table->foreignId('template_id')->nullable();
            $table->integer('skip_failed_message')->default(0);

            // Foreign Keys
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
            $table->foreign('default_mail_list_id')
                ->references('id')
                ->on('mailing_mail_lists')
                ->onDelete('set null');
            $table->foreign('template_id')
                ->references('id')
                ->on('mailing_templates')
                ->onDelete('cascade');
            $table->foreign('tracking_domain_id')
                ->references('id')
                ->on('mailing_tracking_domains')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_campaigns');
    }
};
