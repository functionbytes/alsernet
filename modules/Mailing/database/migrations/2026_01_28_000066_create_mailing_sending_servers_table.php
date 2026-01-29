<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_sending_servers', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('admin_id')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('bounce_handler_id')->nullable();
            $table->unsignedInteger('feedback_loop_handler_id')->nullable();
            $table->string('name');
            $table->string('type');
            $table->string('host')->nullable();
            $table->string('aws_access_key_id')->nullable();
            $table->string('aws_secret_access_key')->nullable();
            $table->string('aws_region')->nullable();
            $table->string('domain')->nullable();
            $table->string('api_key')->nullable();
            $table->text('api_secret_key')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_protocol')->nullable();
            $table->string('sendmail_path')->nullable();
            $table->integer('quota_value');
            $table->integer('quota_base');
            $table->string('quota_unit');
            $table->longText('quota')->nullable();
            $table->string('default_from_email')->nullable();
            $table->longText('options')->nullable();
            $table->string('username')->nullable();
            $table->string('status');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('admin_id')->references('id')->on('mailing_admins')->onDelete('set null');
            $table->foreign('bounce_handler_id')->references('id')->on('mailing_bounce_handlers')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('set null');
            $table->foreign('feedback_loop_handler_id')->references('id')->on('mailing_feedback_loop_handlers')->onDelete('set null');

            $table->index('admin_id');
            $table->index('bounce_handler_id');
            $table->index('customer_id');
            $table->index('feedback_loop_handler_id');

            // Foreign Keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('mailing_admins')
                ->onDelete('set null');
            $table->foreign('bounce_handler_id')
                ->references('id')
                ->on('mailing_bounce_handlers')
                ->onDelete('set null');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('set null');
            $table->foreign('feedback_loop_handler_id')
                ->references('id')
                ->on('mailing_feedback_loop_handlers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_sending_servers');
    }
};
