<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_payments', function (Blueprint $table) {
            $table->id('id_return_payment');
            $table->foreignId('id_return_request')->constrained('return_requests')->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('id_employee')->nullable();
            $table->timestamps();
            $table->index('id_return_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_payments');
    }
};
