<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_subscribers', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->foreignId('mail_list_id');
            $table->char('email', 191);
            $table->index('email');
            $table->char('status', 191)->nullable();
            $table->text('from')->nullable();
            $table->text('ip')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->char('subscription_type', 191)->nullable();
            $table->text('tags')->nullable();
            $table->char('verification_status', 100)->nullable();
            $table->dateTime('last_verification_at')->nullable();
            $table->char('last_verification_by', 100)->nullable();
            $table->mediumText('last_verification_result')->nullable();
            $table->char('import_batch_id', 36)->nullable();

            // Foreign Keys
            $table->foreign('mail_list_id')
                ->references('id')
                ->on('mailing_mail_lists')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_subscribers');
    }
};
