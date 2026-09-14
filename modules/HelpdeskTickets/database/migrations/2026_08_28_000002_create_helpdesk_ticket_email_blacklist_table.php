<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lista negra de remitentes de email para la ingesta IMAP de
 * FetchTicketEmailsJob: bloquea por email exacto o por dominio (incluyendo
 * subdominios) antes de crear Customer/Ticket. matched_count/last_matched_at
 * dan visibilidad de si una regla realmente está bloqueando algo.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ticket_email_blacklist', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['email', 'domain'])->default('email');
            $table->string('value');
            $table->text('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('added_by')->nullable();
            $table->unsignedInteger('matched_count')->default(0);
            $table->timestamp('last_matched_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_email_blacklist');
    }
};
