<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submission_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->enum('email_type', ['confirmation', 'admin', 'custom', 'resend'])->default('admin');
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('body_html')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('submission_id');
            $table->index('status');

            $table->foreign('submission_id')
                ->references('id')->on('form_submissions')->cascadeOnDelete();
            $table->foreign('sent_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_emails');
    }
};
