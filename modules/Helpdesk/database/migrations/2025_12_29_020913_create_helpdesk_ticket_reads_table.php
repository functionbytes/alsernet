<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_item_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('ticket_item_id')->references('id')->on('helpdesk_ticket_items')->onDelete('cascade');

            $table->unique(['ticket_item_id', 'user_id'], 'idx_71607');
            $table->index('ticket_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_reads');
    }
};
