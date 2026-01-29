<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_emails', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('automation2_id');
            $table->string('subject')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->longText('plain')->nullable();
            $table->boolean('sign_dkim')->default(1);
            $table->boolean('track_open')->default(1);
            $table->boolean('track_click')->default(1);
            $table->string('action_id')->nullable();
            $table->unsignedBigInteger('tracking_domain_id')->nullable();
            $table->unsignedInteger('template_id')->nullable();
            $table->unsignedInteger('customer_id')->default(0);
            $table->boolean('skip_failed_message')->default(0);
            $table->text('preheader')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('automation2_id')->references('id')->on('mailing_automation2s')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('mailing_templates')->onDelete('set null');
            $table->foreign('tracking_domain_id')->references('id')->on('mailing_tracking_domains')->onDelete('set null');

            $table->index('automation2_id');
            $table->index('customer_id');
            $table->index('template_id');
            $table->index('tracking_domain_id');

            // Foreign Keys
            $table->foreign('automation2_id')
                ->references('id')
                ->on('mailing_automation2s')
                ->onDelete('cascade');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
            $table->foreign('template_id')
                ->references('id')
                ->on('mailing_templates')
                ->onDelete('set null');
            $table->foreign('tracking_domain_id')
                ->references('id')
                ->on('mailing_tracking_domains')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_emails');
    }
};
