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
        Schema::create('chat_hours', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('inbox_id')->nullable();
            $table->tinyInteger('day_of_week')->comment('0=Sunday, 1=Monday, ..., 6=Saturday');
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_enabled')->default(true);
            $table->string('timezone')->default('UTC');
            $table->timestamps();

            $table->index(['account_id', 'day_of_week'], 'business_hours_account_id_day_of_week_index');
            $table->index(['inbox_id', 'day_of_week'], 'business_hours_inbox_id_day_of_week_index');

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
        Schema::dropIfExists('chat_hours');
    }
};
