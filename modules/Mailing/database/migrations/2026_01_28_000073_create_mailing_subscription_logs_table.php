<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_subscription_logs', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('subscription_id');
            $table->unsignedInteger('transaction_id')->nullable();
            $table->string('type');
            $table->longText('data')->nullable();
            $table->char('invoice_uid', 36)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('subscription_id')->references('id')->on('mailing_subscriptions')->onDelete('cascade');

            $table->index('subscription_id');

            // Foreign Keys
            $table->foreign('subscription_id')
                ->references('id')
                ->on('mailing_subscriptions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_subscription_logs');
    }
};
