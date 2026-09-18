<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_ticket_links')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_ticket_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->foreignId('linked_ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->string('link_type', 32)->default('related');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'linked_ticket_id']);
            $table->index(['ticket_id', 'link_type']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_links');
    }
};
