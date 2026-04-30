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
        Schema::create('chat_integrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('inbox_id')->nullable()->index('integrations_hooks_inbox_id_index');
            $table->unsignedBigInteger('account_id')->nullable()->index('integrations_hooks_account_id_index');
            $table->string('app_id');
            $table->integer('hook_type')->default(0);
            $table->string('reference_id')->nullable();
            $table->string('access_token')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

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
        Schema::dropIfExists('chat_integrations');
    }
};
