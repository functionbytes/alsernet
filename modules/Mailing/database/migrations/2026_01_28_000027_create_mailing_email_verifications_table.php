<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_email_verifications', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('result');
            $table->text('details');
            $table->unsignedInteger('subscriber_id');
            $table->unsignedInteger('email_verification_server_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('subscriber_id')->references('id')->on('mailing_subscribers')->onDelete('cascade');
            $table->foreign('email_verification_server_id')->references('id')->on('mailing_email_verification_servers')->onDelete('cascade');

            $table->index('subscriber_id');
            $table->index('email_verification_server_id');

            // Foreign Keys
            $table->foreign('email_verification_server_id')
                ->references('id')
                ->on('mailing_email_verification_servers')
                ->onDelete('cascade');
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('mailing_subscribers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_email_verifications');
    }
};
