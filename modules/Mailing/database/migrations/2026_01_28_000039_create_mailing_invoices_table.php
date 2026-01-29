<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_invoices', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('currency_id');
            $table->string('status');
            $table->string('title');
            $table->text('description');
            $table->string('type');
            $table->longText('metadata')->nullable();
            $table->string('billing_first_name')->nullable();
            $table->string('billing_last_name')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->integer('billing_country_id')->nullable();
            $table->decimal('fee', 16, 2)->default(0.00);
            $table->string('number')->nullable();
            $table->unsignedInteger('subscription_id')->nullable();
            $table->unsignedInteger('new_plan_id')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('currency_id')->references('id')->on('mailing_currencies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('cascade');
            $table->foreign('new_plan_id')->references('id')->on('mailing_plans')->onDelete('set null');
            $table->foreign('subscription_id')->references('id')->on('mailing_subscriptions')->onDelete('set null');

            $table->index('currency_id');
            $table->index('customer_id');
            $table->index('new_plan_id');
            $table->index('subscription_id');

            // Foreign Keys
            $table->foreign('currency_id')
                ->references('id')
                ->on('mailing_currencies')
                ->onDelete('cascade');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
            $table->foreign('new_plan_id')
                ->references('id')
                ->on('mailing_plans')
                ->onDelete('set null');
            $table->foreign('subscription_id')
                ->references('id')
                ->on('mailing_subscriptions')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_invoices');
    }
};
