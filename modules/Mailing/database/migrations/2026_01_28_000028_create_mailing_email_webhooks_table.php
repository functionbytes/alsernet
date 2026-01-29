<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_email_webhooks', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->char('uid', 36)->unique();
            $table->string('type');
            $table->text('endpoint');
            $table->unsignedInteger('email_id');
            $table->unsignedInteger('email_link_id')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('email_id')->references('id')->on('mailing_emails')->onDelete('cascade');
            $table->foreign('email_link_id')->references('id')->on('mailing_email_links')->onDelete('set null');

            $table->index('email_id');
            $table->index('email_link_id');

            // Foreign Keys
            $table->foreign('email_id')
                ->references('id')
                ->on('mailing_emails')
                ->onDelete('cascade');
            $table->foreign('email_link_id')
                ->references('id')
                ->on('mailing_email_links')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_email_webhooks');
    }
};
