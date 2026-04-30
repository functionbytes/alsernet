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
        Schema::create('chat_inboxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->string('channel_type');
            $table->unsignedBigInteger('channel_id');
            $table->string('name');
            $table->string('timezone')->default('UTC');
            $table->boolean('greeting_enabled')->default(false);
            $table->text('greeting_message')->nullable();
            $table->boolean('working_hours_enabled')->default(false);
            $table->text('out_of_office_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'channel_type'], 'inboxes_account_id_channel_type_index');
            $table->index(['channel_type', 'channel_id'], 'inboxes_channel_type_channel_id_index');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_inboxes');
    }
};
