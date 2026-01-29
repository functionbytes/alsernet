<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_transactions', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('invoice_id');
            $table->longText('error')->nullable();
            $table->string('status');
            $table->string('method')->nullable();
            $table->boolean('allow_manual_review')->default(0);
            $table->text('checkout_id')->nullable();
            $table->text('payment_service_id')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('invoice_id')->references('id')->on('mailing_invoices')->onDelete('cascade');

            $table->index('invoice_id');

            // Foreign Keys
            $table->foreign('invoice_id')
                ->references('id')
                ->on('mailing_invoices')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_transactions');
    }
};
