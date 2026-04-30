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
        Schema::create('chat_message_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('message_templates_account_id_index');
            $table->unsignedBigInteger('user_id')->nullable()->index('message_templates_user_id_foreign');
            $table->string('name');
            $table->text('content');
            $table->string('shortcut', 50)->nullable();
            $table->string('category', 100)->nullable()->index('message_templates_category_index');
            $table->boolean('is_public')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'shortcut'], 'message_templates_account_id_shortcut_unique');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_templates');
    }
};
