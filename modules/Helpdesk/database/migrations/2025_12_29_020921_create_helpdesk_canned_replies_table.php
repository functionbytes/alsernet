<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_canned_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->text('short_code')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('helpdesk_conversations')->onDelete('set null');

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_canned_replies');
    }
};
