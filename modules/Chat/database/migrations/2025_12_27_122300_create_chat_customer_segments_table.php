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
        Schema::create('chat_customer_segments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('customer_segments_account_id_index');
            $table->unsignedBigInteger('user_id')->index('customer_segments_user_id_index');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('filter_criteria')->nullable();
            $table->boolean('is_dynamic')->default(true)->index('customer_segments_is_dynamic_index');
            $table->integer('customer_count')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_customer_segments');
    }
};
