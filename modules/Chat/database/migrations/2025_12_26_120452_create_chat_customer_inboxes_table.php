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
        Schema::create('chat_customer_inboxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id')->index('customer_inboxes_customer_id_index');
            $table->unsignedBigInteger('inbox_id');
            $table->string('source_id');
            $table->boolean('hmac_verified')->default(false);
            $table->string('pubsub_token')->nullable();
            $table->timestamps();

            $table->unique(['inbox_id', 'source_id'], 'customer_inboxes_inbox_id_source_id_unique');

            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('chat_customers')->onDelete('cascade');
            $table->foreign('inbox_id')->references('id')->on('chat_inboxes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_customer_inboxes');
    }
};
