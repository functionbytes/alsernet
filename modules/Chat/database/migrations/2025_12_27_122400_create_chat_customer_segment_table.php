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
        Schema::create('chat_customer_segment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_segment_id')->index('customer_segment_contact_contact_segment_id_index');
            $table->unsignedBigInteger('customer_id')->index('customer_segment_contact_customer_id_index');
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['customer_segment_id', 'customer_id'], 'customer_segment_contact_contact_segment_id_customer_id_unique');

            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('chat_customers')->onDelete('cascade');
            $table->foreign('customer_segment_id')->references('id')->on('chat_customer_segments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_customer_segment');
    }
};
