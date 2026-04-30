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
        Schema::create('chat_canneds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('canned_responses_account_id_index');
            $table->unsignedBigInteger('user_id')->nullable()->index('canned_responses_user_id_foreign');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('visibility')->default('everyone')->index('canned_responses_visibility_index');
            $table->string('short_code')->index('canned_responses_short_code_index');
            $table->text('content');
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

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
        Schema::dropIfExists('chat_canneds');
    }
};
