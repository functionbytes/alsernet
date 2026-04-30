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
        if (! Schema::hasTable('chat_conversation_priorities')) {
            Schema::create('chat_conversation_priorities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->string('name'); // Low, Medium, High, Urgent
                $table->string('slug')->unique();
                $table->string('color')->default('#gray');
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['account_id', 'is_active']);
                $table->index('order');

                // Foreign key to chat_accounts table
                $table->foreign('account_id', 'chat_conversation_priorities_account_id_foreign')
                    ->references('id')
                    ->on('chat_accounts')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_priorities');
    }
};
