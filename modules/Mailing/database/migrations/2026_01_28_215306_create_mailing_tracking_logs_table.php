<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->char('runtime_message_id', 191)->nullable();
            $table->unique('runtime_message_id');
            $table->char('message_id', 191)->nullable();
            $table->unique('message_id');
            $table->foreignId('customer_id');
            $table->foreignId('sending_server_id');
            $table->foreignId('campaign_id')->nullable();
            $table->foreignId('subscriber_id');
            $table->char('status', 191);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreignId('auto_trigger_id')->nullable();
            $table->foreignId('sub_account_id')->nullable();
            $table->foreignId('email_id')->nullable();

            // Foreign Keys
            $table->foreign('auto_trigger_id')
                ->references('id')
                ->on('mailing_auto_triggers')
                ->onDelete('cascade');
            $table->foreign('campaign_id')
                ->references('id')
                ->on('mailing_campaigns')
                ->onDelete('cascade');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
            $table->foreign('email_id')
                ->references('id')
                ->on('mailing_emails')
                ->onDelete('cascade');
            $table->foreign('sending_server_id')
                ->references('id')
                ->on('mailing_sending_servers')
                ->onDelete('cascade');
            $table->foreign('sub_account_id')
                ->references('id')
                ->on('mailing_sub_accounts')
                ->onDelete('cascade');
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('mailing_subscribers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_tracking_logs');
    }
};
