<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_customer_label', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('label_id')->index('customer_label_label_id_index');
            $table->timestamps();

            $table->unique(['customer_id', 'label_id'], 'customer_label_customer_id_label_id_unique');

            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('chat_customers')->onDelete('cascade');
            $table->foreign('label_id')->references('id')->on('chat_labels')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_customer_label');
    }
};
