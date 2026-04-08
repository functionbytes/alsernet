<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        if (Schema::connection('helpdesk')->hasTable('helpdesk_ticket_time_entries')) {
            return;
        }

        Schema::connection('helpdesk')->create('helpdesk_ticket_time_entries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->string('description')->nullable();
            $table->unsignedInteger('minutes');
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('helpdesk_tickets')
                ->cascadeOnDelete();

            $table->index('ticket_id');
            $table->index('user_id');
            $table->index(['ticket_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_ticket_time_entries');
    }
};
