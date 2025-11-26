<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_documents', function (Blueprint $table) {
            $table->id();

            $table->string('uid')->unique();
            $table->string('type')->nullable();
            $table->string('proccess')->nullable();

            $table->enum('source', ['email', 'api', 'whatsapp', 'manual', 'wp'])
                ->nullable()
                ->comment('Source origin: email, api, whatsapp, manual, or wp');

            $table->timestamp('confirmed_at')->nullable()
                ->comment('When the document upload was confirmed');

            $table->timestamp('reminder_at')->nullable();

            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('cart_id')->nullable();


            // Datos de la orden
            $table->string('order_reference', 64)->nullable();
            $table->datetime('order_date')->nullable();

            // Datos del cliente
            $table->string('customer_firstname', 32)->nullable();
            $table->string('customer_lastname', 32)->nullable();
            $table->string('customer_email', 128)->nullable();
            $table->string('customer_dni', 32)->nullable();
            $table->string('customer_company', 64)->nullable();

            $table->timestamps();

            // Índices
            $table->index('customer_firstname');
            $table->index('customer_lastname');
            $table->index('customer_email');
            $table->index('customer_dni');
            $table->index('order_reference');
            $table->index('order_id');
            $table->index('order_date');
            $table->index(['customer_firstname', 'customer_lastname']);

/*
            $table->foreign('order_id')
                ->references('id_order')
                ->on('aalv_order')
                ->onDelete('cascade');

            $table->foreign('customer_id')
                ->references('id_customer')
                ->on('aalv_customer')
                ->onDelete('cascade');

            $table->foreign('cart_id')
                ->references('id_cart')
                ->on('aalv_cart')
                ->onDelete('cascade');
*/

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_documents');
    }
};
