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
        // Drop foreign keys first if they exist
        Schema::table('chat_webhooks', function (Blueprint $table) {
            $table->dropForeign('chat_webhooks_account_id_foreign');
            $table->dropForeign('chat_webhooks_inbox_id_foreign');
        });

        // Drop the unique constraint
        Schema::table('chat_webhooks', function (Blueprint $table) {
            $table->dropUnique('webhooks_account_id_url_unique');
        });

        // Re-add foreign keys
        Schema::table('chat_webhooks', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('inbox_id')->references('id')->on('chat_inboxes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_webhooks', function (Blueprint $table) {
            $table->unique(['account_id', 'url'], 'webhooks_account_id_url_unique');
        });
    }
};
