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
        Schema::create('chat_customer_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('customer_notes_account_id_foreign');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id')->nullable()->index('customer_notes_user_id_index');
            $table->text('content');
            $table->timestamps();

            $table->index(['customer_id', 'created_at'], 'customer_notes_customer_id_created_at_index');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('chat_customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_customer_notes');
    }
};
