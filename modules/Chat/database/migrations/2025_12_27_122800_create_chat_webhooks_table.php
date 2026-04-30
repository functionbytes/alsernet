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
        Schema::create('chat_webhooks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('inbox_id')->nullable()->index('webhooks_inbox_id_foreign');
            $table->string('name');
            $table->string('url');
            $table->integer('webhook_type')->default(0);
            $table->json('subscriptions')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'url'], 'webhooks_account_id_url_unique');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('inbox_id')->references('id')->on('chat_inboxes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_webhooks');
    }
};
