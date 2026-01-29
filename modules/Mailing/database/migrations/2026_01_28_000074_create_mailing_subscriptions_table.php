<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->string('status');
            $table->dateTime('current_period_ends_at')->nullable();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('plan_id');
            $table->boolean('is_recurring')->default(1);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('mailing_plans')->onDelete('cascade');

            $table->index('customer_id');
            $table->index('plan_id');

            // Foreign Keys
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
            $table->foreign('plan_id')
                ->references('id')
                ->on('mailing_plans')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_subscriptions');
    }
};
