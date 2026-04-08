<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_open')->default(true);
            $table->boolean('stops_sla_timer')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('is_default');
            $table->index('is_open');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_statuses');
    }
};
