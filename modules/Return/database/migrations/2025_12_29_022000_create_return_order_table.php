<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('type')->nullable();
            $table->string('proccess')->nullable();
            $table->string('label')->nullable();
            $table->integer('order_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('cart_id')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('customer_id');
            $table->index('cart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_orders');
    }
};
