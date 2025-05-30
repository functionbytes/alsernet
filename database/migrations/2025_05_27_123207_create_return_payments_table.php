<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('return_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->decimal('amount', 8, 2);
            $table->enum('payment_method', ['bank_transfer', 'credit_card', 'paypal', 'wallet', 'cash', 'other'])->default('bank_transfer');
            $table->string('transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->datetime('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('employee_id')->default(1);
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('return_requests')->onDelete('cascade');

            $table->index(['request_id']);
            $table->index(['payment_status']);
            $table->index(['payment_method']);
            $table->index(['processed_at']);
            $table->index(['employee_id']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_payments');
    }
}
