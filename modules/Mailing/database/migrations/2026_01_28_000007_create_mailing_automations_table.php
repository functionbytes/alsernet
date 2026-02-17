<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_automations', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->char('uid', 36)->unique();
            $table->string('name');
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('mail_list_id');
            $table->string('time_zone')->nullable();
            $table->string('status');
            $table->longText('data')->nullable();
            $table->text('segment_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('cascade');
            $table->foreign('mail_list_id')->references('id')->on('mailing_mail_lists')->onDelete('cascade');

            $table->index('customer_id');
            $table->index('mail_list_id');

            // Foreign Keys
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
            $table->foreign('mail_list_id')
                ->references('id')
                ->on('mailing_mail_lists')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_automations');
    }
};
