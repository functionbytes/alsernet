<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title')->nullable();
            $table->longText('body');
            $table->boolean('is_pinned')->default(false);
            $table->string('color')->default('yellow');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ticket_id')->references('id')->on('helpdesk_tickets')->onDelete('cascade');

            $table->index('ticket_id');
            $table->index('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_notes');
    }
};
